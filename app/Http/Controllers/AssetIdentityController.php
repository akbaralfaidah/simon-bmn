<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AuditEvent;
use App\Models\WorkRecord;
use App\Services\AccessScope;
use App\Services\AssetIdentityService;
use App\Services\UploadScanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetIdentityController extends Controller
{
    public function store(Request $request, AccessScope $scope, AssetIdentityService $identities): RedirectResponse
    {
        $asset = Asset::findOrFail($request->input('asset_id'));
        abort_unless($scope->coordinate($request->user(), $asset) || $scope->inspect($request->user(), $asset), 404);
        $data = $request->validate([
            'asset_id' => 'required|uuid', 'title' => 'required|string|max:255', 'notes' => 'required|string|max:5000',
            'register_type' => 'required|in:ASP,PSP', 'reference_number' => 'required|string|max:255',
            'reference_date' => 'required|date_format:Y-m-d|before_or_equal:today', 'source_status' => 'required|string|max:255',
            'ticket_number' => 'nullable|string|max:255', 'bast_number' => 'required|string|max:255',
            'satker_code' => 'required|string|max:100', 'item_code' => 'required|string|max:255', 'nup' => 'required|string|max:255',
            'document' => 'required|file|mimes:pdf|max:10240',
        ]);
        app(UploadScanner::class)->assertClean($request->file('document')->getRealPath());
        $path = $request->file('document')->store('documents/identity-transitions', 'local');
        abort_unless($path, 503);
        try {
            DB::transaction(function () use ($request, $asset, $data, $path, $scope, $identities) {
                $locked = Asset::whereKey($asset->id)->lockForUpdate()->firstOrFail();
                abort_unless($scope->coordinate($request->user(), $locked) || $scope->inspect($request->user(), $locked), 404);
                abort_unless($locked->status === 'active', 422, 'Transisi hanya untuk aset aktif.');
                $after = collect($data)->only(['satker_code', 'item_code', 'nup'])->all();
                $identities->assertAvailable($after, $locked->id);
                $record = WorkRecord::create(['kind' => 'registers', 'asset_id' => $locked->id, 'unit_id' => $locked->room?->unit_id,
                    'title' => $data['title'], 'status' => 'submitted', 'created_by' => $request->user()->id,
                    'data' => [...collect($data)->except(['asset_id', 'title', 'document', 'satker_code', 'item_code', 'nup'])->all(),
                        'before_identity' => $identities->snapshot($locked), 'after_identity' => $after, 'asset_version' => $locked->version,
                        'mapping_status' => 'unverified', 'document_path' => $path, 'document_scan_status' => 'clean',
                        'document_checksum' => hash_file('sha256', $request->file('document')->getRealPath()),
                    ],
                ]);
                AuditEvent::record($record, 'registers.submitted', ['before' => $identities->snapshot($locked), 'after' => $after], $record->unit_id);
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return back()->with('success', 'Usulan transisi disimpan. Identitas master belum berubah; tunggu verifikasi Koordinator lain.');
    }

    public function apply(Request $request, WorkRecord $record, AccessScope $scope, AssetIdentityService $identities): RedirectResponse
    {
        abort_unless($record->kind === 'registers' && $record->asset && $scope->coordinate($request->user(), $record->asset), 404);
        abort_if($record->created_by === $request->user()->id, 403, 'Pengusul tidak boleh menerapkan transisinya sendiri.');
        $data = $request->validate(['version' => 'required|integer|min:1', 'notes' => 'required|string|max:5000']);
        DB::transaction(function () use ($request, $record, $scope, $identities, $data) {
            $identities->lock();
            $asset = Asset::whereKey($record->asset_id)->lockForUpdate()->firstOrFail();
            $locked = WorkRecord::whereKey($record->id)->lockForUpdate()->firstOrFail();
            abort_unless($scope->coordinate($request->user(), $asset), 404);
            abort_unless($locked->version === (int) $data['version'] && $locked->status === 'approved', 422, 'Usulan telah berubah atau belum disetujui.');
            $transition = $locked->data;
            abort_unless(($transition['mapping_status'] ?? '') === 'verified' && isset($transition['after_identity'], $transition['before_identity']), 422, 'Register lama/belum dipetakan tidak dapat mengubah identitas. Buat usulan lengkap dengan bukti.');
            abort_unless($asset->status === 'active' && $asset->version === (int) $transition['asset_version'] && $identities->snapshot($asset) == $transition['before_identity'], 422, 'Aset berubah sejak pengajuan. Ajukan ulang berdasarkan data terbaru.');
            $this->assertEvidence($locked);
            $identities->assertAvailable($transition['after_identity'], $asset->id);
            $asset->identifiers()->create(['identifier_type' => 'bmn_previous', 'identifier_value' => (string) ($asset->nup ?? ''),
                'identity_snapshot' => $identities->snapshot($asset), 'assigned_date' => $asset->identity_verified_at?->toDateString(),
                'valid_until' => now(), 'work_record_id' => $locked->id, 'recorded_by' => $request->user()->id,
            ]);
            $asset->update([...$transition['after_identity'], 'identity_verified_at' => now(), 'version' => $asset->version + 1]);
            $locked->update(['status' => 'resolved', 'version' => $locked->version + 1, 'data' => [...$transition,
                'mapping_status' => 'applied', 'applied_at' => now()->toIso8601String(), 'applied_by' => $request->user()->id, 'resolution' => $data['notes'],
            ]]);
            AuditEvent::record($asset, 'asset.identity_transitioned', ['record_id' => $locked->id, 'before' => $transition['before_identity'], 'after' => $transition['after_identity']], $locked->unit_id);
            AuditEvent::record($locked, 'registers.applied', ['notes' => $data['notes']], $locked->unit_id);
        }, 3);

        return back()->with('success', 'Identitas BMN diperbarui. ID internal, transaksi, dan riwayat identitas lama tetap dipertahankan.');
    }

    public function evidence(Request $request, WorkRecord $record, AccessScope $scope): StreamedResponse
    {
        abort_unless($record->kind === 'registers' && $record->asset && ($scope->coordinate($request->user(), $record->asset) || $scope->inspect($request->user(), $record->asset)), 404);
        $this->assertEvidence($record);
        AuditEvent::record($record, 'registers.evidence_downloaded', [], $record->unit_id);

        return Storage::disk('local')->download($record->data['document_path'], 'bukti-transisi-'.$record->id.'.pdf', ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    private function assertEvidence(WorkRecord $record): void
    {
        $data = $record->data;
        abort_unless(($data['document_scan_status'] ?? '') === 'clean' && isset($data['document_path'], $data['document_checksum']) && Storage::disk('local')->exists($data['document_path']), 422, 'Bukti terverifikasi tidak tersedia.');
        abort_unless(hash_equals($data['document_checksum'], hash_file('sha256', Storage::disk('local')->path($data['document_path']))), 422, 'Integritas bukti berubah. Hubungi pengelola.');
    }
}
