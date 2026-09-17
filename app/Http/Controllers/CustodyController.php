<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetOccupancy;
use App\Models\AuditEvent;
use App\Models\CustodyAssignment;
use App\Models\CustodyEvent;
use App\Models\Reservation;
use App\Models\User;
use App\Models\WorkRecord;
use App\Services\AccessScope;
use App\Services\UploadScanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustodyController extends Controller
{
    public function assign(Request $request, Asset $asset): RedirectResponse
    {
        $this->authorize('update', $asset);
        $data = $request->validate(['user_id' => 'required|exists:users,id', 'start_date' => 'required|date', 'notes' => 'required|string|max:5000']);
        abort_if($request->user()->id === (int) $data['user_id'], 403, 'Penetapan untuk diri sendiri harus dibuat koordinator lain.');
        $recipient = User::findOrFail($data['user_id']);
        abort_unless($recipient->status === 'active' && app(AccessScope::class)->assets($recipient)->whereKey($asset->id)->exists(), 422, 'Pemegang harus aktif dan berada dalam cakupan unit aset.');
        DB::transaction(function () use ($request, $asset, $data) {
            $asset = Asset::whereKey($asset->id)->lockForUpdate()->firstOrFail();
            $this->available($asset);
            abort_if(CustodyAssignment::where('asset_id', $asset->id)->whereNotIn('status', ['revoked', 'rejected'])->exists(), 422, 'Penetapan sebelumnya belum selesai.');
            $assignment = CustodyAssignment::create([...$data, 'asset_id' => $asset->id, 'status' => 'pending', 'assigned_by' => $request->user()->id]);
            $this->event($assignment, $request, 'proposed', $data['notes']);
        });

        return back()->with('success', 'Penetapan diajukan. PJ dan pemegang perlu menyelesaikan serah-terima.');
    }

    public function action(Request $request, CustodyAssignment $assignment, string $action): RedirectResponse
    {
        abort_unless(in_array($action, ['handover', 'accept', 'request-return', 'receive', 'reject']), 404);
        $scope = app(AccessScope::class);
        $path = null;
        if (in_array($action, ['handover', 'receive'])) {
            abort_unless($scope->inspect($request->user(), $assignment->asset) && $assignment->user_id !== $request->user()->id, 403);
            $request->validate(['document' => 'required|file|mimes:pdf|max:10240', 'notes' => 'required|string|max:5000']);
            app(UploadScanner::class)->assertClean($request->file('document')->getRealPath());
            $path = $request->file('document')->store('documents/custody', 'local');
            abort_unless($path, 503);
        }
        try {
            DB::transaction(function () use ($request, $assignment, $action, $path, $scope) {
                $asset = Asset::whereKey($assignment->asset_id)->lockForUpdate()->firstOrFail();
                $record = CustodyAssignment::whereKey($assignment->id)->lockForUpdate()->firstOrFail();
                if ($action === 'handover') {
                    abort_unless($record->status === 'pending' && $record->start_date->lte(today()), 422);
                    $this->available($asset);
                    $record->update(['status' => 'handed_over', 'verified_by' => $request->user()->id, 'evidence_path' => $path, 'condition_before' => $asset->condition]);
                    AssetOccupancy::create(['asset_id' => $asset->id, 'user_id' => $record->user_id, 'occupancy_type' => 'custody', 'starts_at' => now(), 'is_active' => true]);
                } elseif ($action === 'accept' || $action === 'request-return') {
                    abort_unless($record->user_id === $request->user()->id, 403);
                    abort_unless($record->status === ($action === 'accept' ? 'handed_over' : 'active'), 422);
                    $record->update($action === 'accept' ? ['status' => 'active', 'accepted_at' => now()] : ['status' => 'return_requested']);
                } elseif ($action === 'receive') {
                    abort_unless($scope->inspect($request->user(), $asset) && $record->user_id !== $request->user()->id, 403);
                    abort_unless($record->status === 'return_requested' || ($record->status === 'received' && ! $record->return_evidence_path), 422);
                    $data = $request->validate(['condition' => 'required|in:Baik,Rusak Ringan,Rusak Berat', 'completeness' => 'required|in:complete,incomplete', 'notes' => 'required|string|max:5000']);
                    $record->update(['status' => 'received', 'received_by' => $request->user()->id, 'condition_after' => $data['condition'], 'return_completeness' => $data['completeness'], 'return_notes' => $data['notes'], 'physically_received_at' => now(), 'return_evidence_path' => $path, 'return_evidence_checksum' => hash_file('sha256', Storage::disk('local')->path($path))]);
                    $asset->update(['condition' => $data['condition'], 'version' => $asset->version + 1]);
                    if ($data['condition'] !== 'Baik' || $data['completeness'] === 'incomplete') {
                        $incident = WorkRecord::create(['kind' => 'incidents', 'title' => 'Tindak lanjut penetapan #'.$record->id, 'asset_id' => $asset->id, 'unit_id' => $asset->room?->unit_id, 'created_by' => $request->user()->id, 'status' => 'submitted',
                            'data' => ['category' => $data['condition'] !== 'Baik' ? 'damage' : 'other', 'custody_assignment_id' => $record->id, 'reported_for_user_id' => $record->user_id, 'condition_before' => $record->condition_before, 'condition_after' => $data['condition'], 'completeness' => $data['completeness'], 'notes' => $data['notes']],
                        ]);
                        AuditEvent::record($incident, 'incidents.custody_return_reported', ['custody_assignment_id' => $record->id], $incident->unit_id);
                    }
                } else {
                    $this->authorize('update', $asset);
                    abort_unless($record->status === 'pending', 422);
                    $request->validate(['notes' => 'required|string|max:5000']);
                    $record->update(['status' => 'rejected']);
                }
                $this->event($record, $request, $action, $request->input('notes', 'Konfirmasi pengguna.'));
            });
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        return back()->with('success', 'Proses penetapan tersimpan.');
    }

    public function revoke(Request $request, CustodyAssignment $assignment): RedirectResponse
    {
        $this->authorize('update', $assignment->asset);
        abort_if($assignment->user_id === $request->user()->id, 403);
        $request->validate(['notes' => 'required|string|max:5000']);
        DB::transaction(function () use ($request, $assignment) {
            $asset = Asset::whereKey($assignment->asset_id)->lockForUpdate()->firstOrFail();
            $record = CustodyAssignment::whereKey($assignment->id)->lockForUpdate()->firstOrFail();
            abort_unless($record->status === 'received', 422, 'Pengembalian fisik harus diperiksa PJ terlebih dahulu.');
            $this->authorize('update', $asset);
            abort_if($record->received_by === $request->user()->id, 403, 'Pemeriksa fisik tidak boleh menyetujui penutupan sendiri.');
            abort_unless($record->physically_received_at && $record->return_evidence_path && Storage::disk('local')->exists($record->return_evidence_path), 422, 'Lengkapi pemeriksaan dan bukti BAST pengembalian terlebih dahulu.');
            abort_unless(hash_equals($record->return_evidence_checksum, hash_file('sha256', Storage::disk('local')->path($record->return_evidence_path))), 422, 'Integritas bukti pengembalian berubah.');
            abort_if(WorkRecord::where('kind', 'incidents')->where('asset_id', $asset->id)->whereIn('status', ['submitted', 'approved'])->exists(), 422, 'Selesaikan tindak lanjut kerusakan/kelengkapan sebelum penutupan.');
            $record->update(['status' => 'revoked', 'end_date' => today()]);
            AssetOccupancy::where('asset_id', $asset->id)->where('occupancy_type', 'custody')->where('user_id', $record->user_id)->where('is_active', true)->update(['is_active' => false, 'ends_at' => now()]);
            $this->event($record, $request, 'revoked', $request->input('notes'));
        });

        return back()->with('success', 'Penetapan ditutup. Riwayat pemegang tetap tersimpan.');
    }

    public function evidence(Request $request, CustodyAssignment $assignment, string $type): StreamedResponse
    {
        $scope = app(AccessScope::class);
        abort_unless($assignment->user_id === $request->user()->id || $scope->coordinate($request->user(), $assignment->asset) || $scope->inspect($request->user(), $assignment->asset), 404);
        abort_unless(in_array($type, ['handover', 'return']), 404);
        $path = $type === 'return' ? $assignment->return_evidence_path : $assignment->evidence_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);
        if ($type === 'return') {
            abort_unless(hash_equals($assignment->return_evidence_checksum, hash_file('sha256', Storage::disk('local')->path($path))), 422, 'Integritas bukti pengembalian berubah.');
        }
        AuditEvent::record($assignment, 'custody.evidence_downloaded', ['type' => $type], $assignment->asset->room?->unit_id);

        return Storage::disk('local')->download($path, 'penetapan-'.$assignment->id.'-'.$type.'.pdf', ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    private function available(Asset $asset): void
    {
        abort_unless($asset->status === 'active' && $asset->condition === 'Baik' && ! $asset->occupancies()->where('is_active', true)->exists(), 422, 'Aset tidak tersedia atau kondisinya belum layak diserahkan.');
        abort_if(WorkRecord::where('kind', 'incidents')->where('asset_id', $asset->id)->whereIn('status', ['submitted', 'approved'])->exists(), 422, 'Selesaikan insiden barang sebelum penetapan atau serah-terima.');
        abort_if(Reservation::where('asset_id', $asset->id)->where('status', 'active')->exists(), 422, 'Aset memiliki reservasi peminjaman.');
    }

    private function event(CustodyAssignment $assignment, Request $request, string $type, string $notes): void
    {
        CustodyEvent::create(['custody_assignment_id' => $assignment->id, 'type' => $type, 'notes' => $notes, 'performed_by' => $request->user()->id]);
        AuditEvent::record($assignment, 'custody.'.$type, ['notes' => $notes], $assignment->asset->room?->unit_id);
    }
}
