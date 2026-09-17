<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetOccupancy;
use App\Models\AuditEvent;
use App\Models\CustodyAssignment;
use App\Models\LoanItem;
use App\Models\MaintenanceLog;
use App\Models\Reservation;
use App\Models\WorkRecord;
use App\Services\AccessScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaintenanceController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['asset_id' => 'required|uuid|exists:assets,id', 'description' => 'required|string|max:5000', 'cost' => 'nullable|numeric|min:0|max:9999999999999.99', 'start_date' => 'required|date|before_or_equal:today', 'incident_id' => 'nullable|integer']);
        DB::transaction(function () use ($data, $request) {
            $asset = Asset::whereKey($data['asset_id'])->lockForUpdate()->firstOrFail();
            $this->authorize('update', $asset);
            abort_unless($asset->status === 'active', 422, 'Aset tidak aktif.');
            abort_if(MaintenanceLog::where('asset_id', $asset->id)->where('status', 'in_progress')->exists(), 422, 'Perawatan aset masih berlangsung.');
            $item = null;
            $custody = null;
            $incident = null;
            if (! empty($data['incident_id'])) {
                $incident = WorkRecord::whereKey($data['incident_id'])->where('kind', 'incidents')->where('asset_id', $asset->id)->lockForUpdate()->firstOrFail();
                abort_unless($incident->status === 'approved', 422, 'Tindak lanjut harus disetujui terlebih dahulu.');
                abort_if((int) ($incident->data['reported_for_user_id'] ?? 0) === $request->user()->id, 403, 'Pemegang tidak boleh menangani tindak lanjutnya sendiri.');
                if (isset($incident->data['custody_assignment_id'])) {
                    $custody = CustodyAssignment::whereKey($incident->data['custody_assignment_id'])->where('asset_id', $asset->id)->firstOrFail();
                    abort_unless($custody->status === 'received' && $custody->physically_received_at, 422, 'Barang penetapan harus sudah diterima dan diperiksa PJ.');
                } else {
                    $item = LoanItem::whereKey($incident->data['loan_item_id'] ?? null)->where('asset_id', $asset->id)->firstOrFail();
                    abort_if($item->request->user_id === $request->user()->id, 403, 'Peminjam tidak boleh menangani tindak lanjut pinjamannya sendiri.');
                    abort_unless($item->status === 'inspected' && $item->physically_received_at, 422, 'Barang harus sudah diterima dan diperiksa PJ.');
                }
            }
            $occupancies = $asset->occupancies()->where('is_active', true);
            if ($item) {
                $occupancies->where(fn ($query) => $query->where('occupancy_type', '!=', 'loan')->orWhereNull('user_id')->orWhere('user_id', '!=', $item->request->user_id));
            }
            if ($custody) {
                $occupancies->where(fn ($query) => $query->where('occupancy_type', '!=', 'custody')->orWhereNull('user_id')->orWhere('user_id', '!=', $custody->user_id));
            }
            abort_if($occupancies->exists(), 422, 'Aset sedang dikuasai pengguna lain.');
            abort_if(Reservation::where('asset_id', $asset->id)->where('status', 'active')->when($item, fn ($query) => $query->where(fn ($query) => $query->whereNull('loan_item_id')->orWhere('loan_item_id', '!=', $item->id)))->exists(), 422, 'Selesaikan reservasi aset lainnya terlebih dahulu.');
            $record = MaintenanceLog::create([...$data, 'loan_item_id' => $item?->id, 'custody_assignment_id' => $custody?->id, 'status' => 'in_progress', 'reported_by' => $request->user()->id]);
            if (! $item && ! $custody) {
                AssetOccupancy::create(['asset_id' => $asset->id, 'user_id' => $request->user()->id, 'occupancy_type' => 'maintenance', 'starts_at' => now(), 'is_active' => true]);
            }
            if ($incident) {
                $followup = $incident->data;
                unset($followup['checked_by'], $followup['checked_condition'], $followup['checked_at'], $followup['checked_notes']);
                $incident->update(['data' => [...$followup, 'maintenance_id' => $record->id], 'version' => $incident->version + 1]);
            }
            AuditEvent::record($record, 'maintenance.started', [], $asset->room?->unit_id);
        });

        return back()->with('success', 'Perawatan dicatat. Aset diblokir dari peminjaman sampai diperiksa.');
    }

    public function complete(Request $request, MaintenanceLog $maintenance): RedirectResponse
    {
        $data = $request->validate(['condition' => 'required|in:Baik,Rusak Ringan,Rusak Berat', 'notes' => 'required|string|max:5000']);
        DB::transaction(function () use ($request, $maintenance, $data) {
            $asset = Asset::whereKey($maintenance->asset_id)->lockForUpdate()->firstOrFail();
            abort_unless(app(AccessScope::class)->inspect($request->user(), $asset), 403);
            $record = MaintenanceLog::whereKey($maintenance->id)->lockForUpdate()->firstOrFail();
            abort_unless($record->status === 'in_progress', 422);
            $item = $record->loan_item_id ? LoanItem::findOrFail($record->loan_item_id) : null;
            abort_if($item && $item->request->user_id === $request->user()->id, 403);
            abort_if($record->custodyAssignment && $record->custodyAssignment->user_id === $request->user()->id, 403);
            $record->update(['status' => 'completed', 'end_date' => today(), 'inspected_by' => $request->user()->id, 'result_notes' => $data['notes']]);
            $asset->update(['condition' => $data['condition']]);
            AssetOccupancy::where('asset_id', $asset->id)->where('occupancy_type', 'maintenance')->where('is_active', true)->update(['is_active' => false, 'ends_at' => now()]);
            if ($record->incident_id) {
                $incident = WorkRecord::whereKey($record->incident_id)->lockForUpdate()->firstOrFail();
                abort_unless($incident->status === 'approved', 422);
                $incident->update(['data' => [...$incident->data, 'checked_by' => $request->user()->id, 'checked_condition' => $data['condition'], 'checked_notes' => $data['notes'], 'checked_at' => now()->toIso8601String()], 'version' => $incident->version + 1]);
            }
            AuditEvent::record($record, 'maintenance.inspected', $data, $asset->room?->unit_id);
        });

        return back()->with('success', 'Perawatan selesai berdasarkan kondisi hasil pemeriksaan.');
    }
}
