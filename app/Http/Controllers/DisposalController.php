<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AuditEvent;
use App\Models\Disposal;
use App\Models\Reservation;
use App\Services\AccessScope;
use App\Services\UploadScanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DisposalController extends Controller
{
    public function propose(Request $request, Asset $asset): RedirectResponse
    {
        $scope = app(AccessScope::class);
        abort_unless($scope->coordinate($request->user(), $asset) || $scope->inspect($request->user(), $asset), 403);
        $data = $request->validate(['reason' => 'required|string|max:5000']);
        DB::transaction(function () use ($request, $asset, $data) {
            $asset = Asset::whereKey($asset->id)->lockForUpdate()->firstOrFail();
            abort_unless($asset->status === 'active', 422);
            abort_if(Disposal::where('asset_id', $asset->id)->where('status', 'proposed')->exists(), 422, 'Usulan masih menunggu.');
            $record = Disposal::create(['asset_id' => $asset->id, 'reason' => $data['reason'], 'status' => 'proposed', 'proposed_by' => $request->user()->id]);
            AuditEvent::record($record, 'disposal.proposed', [], $asset->room?->unit_id);
        });

        return back()->with('success', 'Usulan dibuat; aset belum dihapus.');
    }

    public function approve(Request $request, Disposal $disposal): RedirectResponse
    {
        $this->authorize('update', $disposal->asset);
        abort_if($disposal->proposed_by === $request->user()->id, 403, 'Usulan sendiri tidak boleh disetujui.');
        $data = $request->validate(['sk_number' => 'required|string|max:255', 'sk_date' => 'required|date|before_or_equal:today', 'sk_issuer' => 'required|string|max:255', 'document' => 'required|file|mimes:pdf|max:10240']);
        app(UploadScanner::class)->assertClean($request->file('document')->getRealPath());
        $path = $request->file('document')->store('documents/disposals', 'local');
        abort_unless($path, 503);
        try {
            DB::transaction(function () use ($request, $disposal, $data, $path) {
                $asset = Asset::whereKey($disposal->asset_id)->lockForUpdate()->firstOrFail();
                $this->authorize('update', $asset);
                $record = Disposal::whereKey($disposal->id)->lockForUpdate()->firstOrFail();
                abort_unless($record->status === 'proposed' && $asset->status === 'active', 422);
                abort_if($asset->occupancies()->where('is_active', true)->exists() || Reservation::where('asset_id', $asset->id)->where('status', 'active')->exists(), 422, 'Aset masih dipakai atau direservasi.');
                $record->update(['sk_number' => $data['sk_number'], 'sk_date' => $data['sk_date'], 'sk_issuer' => $data['sk_issuer'], 'evidence_path' => $path, 'status' => 'approved', 'approved_by' => $request->user()->id]);
                $asset->update(['status' => 'disposed', 'is_loanable' => false]);
                AuditEvent::record($record, 'disposal.approved', ['checksum' => hash_file('sha256', Storage::disk('local')->path($path))], $asset->room?->unit_id);
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return back()->with('success', 'Penghapusan dicatat beserta SK. Riwayat aset tetap tersimpan.');
    }
}
