<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetOccupancy;
use App\Models\AuditEvent;
use App\Models\Bast;
use App\Models\LoanRequest;
use App\Models\Reservation;
use App\Models\User;
use App\Models\WorkRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WorkflowService
{
    public function __construct(private AccessScope $scope) {}

    public function transition(LoanRequest $loan, User $actor, string $action, array $data = []): void
    {
        DB::transaction(function () use ($loan, $actor, $action, $data) {
            $loan = LoanRequest::whereKey($loan->id)->lockForUpdate()->firstOrFail();
            $assets = Asset::whereIn('id', $loan->items()->pluck('asset_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $loan->load('items.asset');
            abort_unless($this->scope->viewLoan($actor, $loan), 403);
            $before = $loan->status;
            if (in_array($action, ['approve', 'reject', 'revise', 'close', 'cancel-item', 'approve-extension', 'reject-extension'], true)) {
                abort_unless($this->scope->decideLoan($actor, $loan), 403);
            }
            if ($action === 'approve') {
                $this->require($loan->status === 'pending_approval', 'Pengajuan sudah diproses.');
                $this->require($loan->end_date >= today()->toDateString(), 'Jadwal pengajuan sudah berakhir.');
                foreach ($loan->items as $item) {
                    $asset = $assets[$item->asset_id];
                    $this->require($asset->status === 'active' && $asset->is_loanable && $asset->condition === 'Baik', 'Salah satu aset tidak layak dipinjam.');
                    $this->require(! $asset->occupancies()->where('is_active', true)->exists(), 'Salah satu aset masih dikuasai pengguna lain.');
                    $this->checkSchedule($asset->id, $loan->start_date, $loan->end_date);
                }
                foreach ($loan->items as $item) {
                    $item->update(['status' => 'approved']);
                    Reservation::create(['asset_id' => $item->asset_id, 'loan_item_id' => $item->id, 'start_date' => $loan->start_date.' 00:00:00', 'end_date' => $loan->end_date.' 23:59:59', 'status' => 'active']);
                }
                $loan->update(['status' => 'approved', 'coordinator_id' => $actor->id]);
                $this->document($loan, $actor, 'loan');
            } elseif ($action === 'revise') {
                $this->require($loan->status === 'pending_approval', 'Hanya pengajuan menunggu yang bisa direvisi.');
                $loan->update(['status' => 'revision_requested', 'decision_reason' => $data['reason'], 'version' => $loan->version + 1]);
            } elseif ($action === 'cancel-item') {
                $items = $loan->items->whereIn('id', $data['item_ids']);
                $this->require($items->count() === count($data['item_ids']), 'Barang tidak termasuk dalam pengajuan.');
                foreach ($items as $item) {
                    $this->require(in_array($item->status, ['approved', 'prepared']) && ! $item->handed_at, 'Hanya item belum diserahkan yang dapat dibatalkan.');
                    $item->update(['status' => 'cancelled', 'notes' => $data['reason']]);
                    $item->reservation()->where('status', 'active')->update(['status' => 'cancelled']);
                }
                if (! $loan->items()->whereNotIn('status', ['returned', 'cancelled'])->exists()) {
                    $loan->update(['status' => $loan->items()->where('status', 'returned')->exists() ? 'completed' : 'cancelled']);
                }
            } elseif ($action === 'reject' || $action === 'cancel') {
                if ($action === 'cancel') {
                    abort_unless($loan->user_id === $actor->id, 403);
                }
                $this->require(in_array($loan->status, $action === 'cancel' ? ['draft', 'revision_requested', 'pending_approval', 'approved'] : ['pending_approval']), 'Pengajuan tidak dapat dibatalkan/ditolak pada tahap ini.');
                $this->require(! $loan->items->contains(fn ($item) => $item->handed_at !== null), 'Barang sudah diserahkan. Gunakan proses pengembalian.');
                $loan->update(['status' => $action === 'cancel' ? 'cancelled' : 'rejected', 'decision_reason' => $data['reason']]);
                Reservation::whereIn('loan_item_id', $loan->items->pluck('id'))->where('status', 'active')->update(['status' => 'cancelled']);
                $loan->items()->update(['status' => $loan->status]);
            } elseif (in_array($action, ['prepare', 'handover', 'accept', 'request-return', 'inspect', 'close'], true)) {
                $ids = $data['item_ids'];
                $items = $loan->items->whereIn('id', $ids);
                $this->require($items->count() === count($ids), 'Barang tidak termasuk dalam pengajuan ini.');
                $this->require(in_array($loan->status, ['approved', 'active', 'returning']), 'Status pengajuan tidak sesuai.');
                foreach ($items as $item) {
                    if (in_array($action, ['prepare', 'handover', 'inspect'], true)) {
                        abort_unless($actor->id !== $loan->user_id && $this->scope->inspect($actor, $item->asset), 403);
                    }
                    if (in_array($action, ['accept', 'request-return'], true)) {
                        abort_unless($actor->id === $loan->user_id, 403);
                    }
                    if ($action === 'prepare') {
                        $this->require($item->status === 'approved', 'Barang bukan dalam tahap persiapan.');
                        $item->update(['status' => 'prepared', 'prepared_by' => $actor->id, 'condition_before' => $item->asset->condition, 'checklist' => ['checked' => true, 'notes' => $data['notes'] ?? null]]);
                    } elseif ($action === 'handover') {
                        $this->require($item->status === 'prepared', 'PJ harus memeriksa barang terlebih dahulu.');
                        $this->require(! $item->media()->whereNull('replacement_media_id')->where(fn ($query) => $query->where('processing_status', '!=', 'ready')->orWhere('scan_status', '!=', 'clean'))->exists(), 'Tunggu pemeriksaan seluruh foto bukti sebelum penyerahan.');
                        $this->checkSchedule($item->asset_id, $loan->start_date, $loan->end_date, $item->id);
                        $this->require(Bast::whereMorphedTo('reference', $loan)->where('bast_type', 'loan')->where('status', 'verified')->exists(), 'Dokumen bertanda tangan harus diunggah peminjam dan diverifikasi koordinator.');
                        $this->require(today()->toDateString() >= $loan->start_date && today()->toDateString() <= $loan->end_date, 'Serah terima hanya pada masa pinjam.');
                        $this->require($item->asset->status === 'active' && $item->asset->condition === 'Baik' && ! $item->asset->occupancies()->where('is_active', true)->exists(), 'Barang tidak tersedia untuk diserahkan.');
                        AssetOccupancy::create(['asset_id' => $item->asset_id, 'user_id' => $loan->user_id, 'occupancy_type' => 'loan', 'starts_at' => now(), 'is_active' => true]);
                        $item->update(['status' => 'handed_over', 'handed_at' => now()]);
                    } elseif ($action === 'accept') {
                        $this->require($item->status === 'handed_over', 'Barang belum diserahkan PJ.');
                        $item->update(['status' => 'active', 'accepted_at' => now()]);
                        $loan->update(['status' => 'active']);
                    } elseif ($action === 'request-return') {
                        $this->require($item->status === 'active', 'Hanya barang yang sedang dipinjam dapat dikembalikan.');
                        $item->update(['status' => 'return_requested', 'return_requested_at' => now(), 'notes' => $data['notes'] ?? null]);
                        $loan->update(['status' => 'returning']);
                    } elseif ($action === 'inspect') {
                        $this->require($item->status === 'return_requested', 'Peminjam belum mengajukan pengembalian.');
                        $item->update(['status' => 'inspected', 'condition_after' => $data['condition'], 'inspected_by' => $actor->id, 'physically_received_at' => now(), 'inspected_at' => now(), 'notes' => $data['notes'] ?? null]);
                        $item->asset->update(['condition' => $data['condition']]);
                        $item->update(['checklist' => [...($item->checklist ?? []), 'return_completeness' => $data['completeness'], 'return_notes' => $data['notes'] ?? null]]);
                        if ($data['condition'] !== 'Baik' || $data['completeness'] === 'incomplete') {
                            $incident = WorkRecord::create([
                                'kind' => 'incidents', 'title' => 'Tindak lanjut pengembalian #'.$loan->id,
                                'asset_id' => $item->asset_id, 'unit_id' => $item->asset->room?->unit_id,
                                'created_by' => $actor->id, 'status' => 'submitted',
                                'data' => ['category' => 'damage', 'loan_request_id' => $loan->id, 'loan_item_id' => $item->id,
                                    'reported_for_user_id' => $loan->user_id, 'condition_before' => $item->condition_before,
                                    'condition_after' => $data['condition'], 'completeness' => $data['completeness'], 'notes' => $data['notes'] ?? null],
                            ]);
                            AuditEvent::record($incident, 'incidents.return_reported', ['loan_item_id' => $item->id], $incident->unit_id);
                        }
                        $this->document($loan, $actor, 'return', [$item->id]);
                    } elseif ($action === 'close') {
                        $this->require($item->status === 'inspected', 'PJ harus memeriksa pengembalian terlebih dahulu.');
                        $this->require(! $item->media()->whereNull('replacement_media_id')->where(fn ($query) => $query->where('processing_status', '!=', 'ready')->orWhere('scan_status', '!=', 'clean'))->exists(), 'Tunggu pemeriksaan seluruh foto bukti sebelum penutupan.');
                        $this->require(Bast::whereMorphedTo('reference', $loan)->where('bast_type', 'return')->where('status', 'verified')->whereJsonContains('snapshot->item_ids', $item->id)->exists(), 'Unggah dan verifikasi BAST pengembalian untuk barang ini sebelum penutupan.');
                        $this->require(! WorkRecord::where('kind', 'incidents')->where('asset_id', $item->asset_id)->whereIn('status', ['submitted', 'approved'])->exists(), 'Selesaikan insiden barang sebelum penutupan.');
                        $item->update(['status' => 'returned', 'closed_at' => now()]);
                        $item->reservation()->where('status', 'active')->update(['status' => 'fulfilled']);
                        AssetOccupancy::where('asset_id', $item->asset_id)->where('user_id', $loan->user_id)->where('occupancy_type', 'loan')->where('is_active', true)->update(['is_active' => false, 'ends_at' => now()]);
                    }
                }
                if ($action === 'close' && ! $loan->items()->whereNotIn('status', ['returned', 'cancelled'])->exists()) {
                    $loan->update(['status' => 'completed']);
                }
            } elseif ($action === 'extend') {
                abort_unless($loan->user_id === $actor->id, 403);
                $this->require($loan->status === 'active' && ! $loan->requested_end_date, 'Perpanjangan hanya untuk pinjaman aktif tanpa permintaan tertunda.');
                $this->require($data['end_date'] > $loan->end_date, 'Tanggal baru harus setelah tanggal selesai.');
                $loan->update(['requested_end_date' => $data['end_date'], 'extension_reason' => $data['reason']]);
            } elseif (in_array($action, ['approve-extension', 'reject-extension'], true)) {
                $this->require($loan->status === 'active' && $loan->requested_end_date !== null, 'Tidak ada perpanjangan yang menunggu.');
                if ($action === 'approve-extension') {
                    foreach ($loan->items as $item) {
                        $this->checkSchedule($item->asset_id, $loan->start_date, $loan->requested_end_date, $item->id);
                    }
                    Reservation::whereIn('loan_item_id', $loan->items->pluck('id'))->where('status', 'active')->update(['end_date' => $loan->requested_end_date.' 23:59:59']);
                    $loan->end_date = $loan->requested_end_date;
                }
                $loan->requested_end_date = null;
                $loan->save();
            }
            AuditEvent::record($loan, 'loan.'.$action, ['before' => $before, 'after' => $loan->status, 'item_ids' => $data['item_ids'] ?? [], 'reason' => $data['reason'] ?? null]);
            $this->notify($loan->user, 'Peminjaman #'.$loan->id.' diperbarui', 'Tindakan: '.$action, route('loans.show', $loan));
        }, 3);
    }

    public function checkSchedule(string $assetId, string $start, string $end, ?int $exceptItem = null): void
    {
        $this->require(! WorkRecord::where('kind', 'incidents')->where('asset_id', $assetId)->whereIn('status', ['submitted', 'approved'])->exists(), 'Barang memiliki laporan kejadian yang belum diselesaikan.');
        $conflict = Reservation::where('asset_id', $assetId)->where('status', 'active')
            ->when($exceptItem, fn ($query) => $query->where('loan_item_id', '!=', $exceptItem))
            ->where('start_date', '<=', $end.' 23:59:59')->where('end_date', '>=', $start.' 00:00:00')->exists();
        $this->require(! $conflict, 'Jadwal bentrok. Pengajuan tetap menunggu agar dapat diperiksa kembali.');
    }

    public function document(LoanRequest $loan, User $actor, string $type, ?array $itemIds = null): Bast
    {
        $key = 'BAST-'.now()->year;
        DB::table('document_sequences')->insertOrIgnore(['key' => $key, 'value' => 0]);
        $sequence = DB::table('document_sequences')->where('key', $key)->lockForUpdate()->first();
        DB::table('document_sequences')->where('key', $key)->increment('value');

        return Bast::create([
            'bast_number' => $key.'-'.str_pad((string) ($sequence->value + 1), 6, '0', STR_PAD_LEFT),
            'bast_type' => $type, 'reference_type' => LoanRequest::class, 'reference_id' => $loan->id,
            'status' => 'draft', 'issued_by' => $actor->id, 'received_by' => $loan->user_id,
            'snapshot' => ['purpose' => $loan->purpose, 'start_date' => $loan->start_date, 'end_date' => $loan->end_date,
                'issuer' => $type === 'return' ? $loan->user->name : $actor->name, 'receiver' => $type === 'return' ? $actor->name : $loan->user->name,
                'template_version' => 1, 'item_ids' => $itemIds ?? $loan->items()->pluck('id')->all(),
                'items' => $loan->items()->when($itemIds, fn ($query) => $query->whereIn('id', $itemIds))->with('asset')->get()->map(fn ($item) => $item->asset->only(['id', 'name', 'item_code', 'nup', 'condition']))->all()],
        ]);
    }

    public function notify(User $user, string $title, string $message, string $url): void
    {
        $user->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'simon.workflow', 'data' => compact('title', 'message', 'url')]);
    }

    private function require(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['workflow' => $message]);
        }
    }
}
