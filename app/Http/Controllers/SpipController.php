<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AuditEvent;
use App\Models\Bast;
use App\Models\SpipRecord;
use App\Services\AccessScope;
use App\Services\DocumentService;
use App\Services\UploadScanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Response;

class SpipController extends Controller
{
    public function index(Request $request): Response
    {
        return app(WorkspaceController::class)->index($request, 'spip');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['period' => 'required|date_format:Y-m', 'asset_id' => 'required|exists:assets,id', 'risk_description' => 'required|string|max:5000', 'control_action' => 'required|string|max:5000', 'due_date' => 'nullable|date|after_or_equal:today']);
        $asset = Asset::findOrFail($data['asset_id']);
        $this->authorize('update', $asset);
        DB::transaction(function () use ($request, $data, $asset) {
            $record = SpipRecord::create([...$data, 'status' => 'open', 'assessor_id' => $request->user()->id]);
            AuditEvent::record($record, 'spip.created', [], $asset->room?->unit_id);
        });

        return back()->with('success', 'Risiko dan rencana pengendalian disimpan.');
    }

    public function action(Request $request, SpipRecord $record, string $action): RedirectResponse
    {
        abort_unless(in_array($action, ['submit', 'resolve', 'revise']), 404);
        $request->validate(['notes' => 'required|string|max:5000', 'version' => 'required|integer|min:1']);
        $scope = app(AccessScope::class);
        $path = null;
        if ($action === 'submit') {
            abort_unless($scope->inspect($request->user(), $record->asset), 403);
            $request->validate(['document' => 'required|file|mimes:pdf|max:10240']);
            app(UploadScanner::class)->assertClean($request->file('document')->getRealPath());
            $path = $request->file('document')->store('documents/spip', 'local');
            abort_unless($path, 503);
        } else {
            $this->authorize('update', $record->asset);
        }
        try {
            DB::transaction(function () use ($request, $record, $action, $path) {
                $locked = SpipRecord::whereKey($record->id)->lockForUpdate()->firstOrFail();
                abort_unless($locked->version === $request->integer('version'), 422, 'Data sudah berubah. Muat ulang.');
                if ($action === 'submit') {
                    abort_unless(in_array($locked->status, ['open', 'revision_requested']), 422);
                    $locked->update(['status' => 'review', 'assigned_to' => $request->user()->id, 'followup' => $request->input('notes'), 'evidence_path' => $path]);
                    $previous = Bast::whereMorphedTo('reference', $locked)->latest('id')->first();
                    $evidence = app(DocumentService::class)->create($locked, $request->user(), 'report', $previous, $request->input('notes'));
                    $evidence->update(['status' => 'uploaded', 'scan_status' => 'clean', 'signed_pdf_path' => $path, 'checksum' => hash_file('sha256', Storage::disk('local')->path($path)), 'snapshot' => [...$evidence->snapshot, 'title' => 'Bukti Tindak Lanjut SPIP', 'purpose' => $request->input('notes'), 'spip' => $locked->only(['period', 'risk_description', 'control_action', 'followup'])]]);
                } else {
                    abort_unless($locked->status === 'review' && $locked->evidence_path, 422);
                    abort_if($locked->assigned_to === $request->user()->id, 403, 'Pelaksana tidak boleh menilai pekerjaannya sendiri.');
                    $evidence = Bast::whereMorphedTo('reference', $locked)->where('signed_pdf_path', $locked->evidence_path)->where('status', 'uploaded')->where('scan_status', 'clean')->lockForUpdate()->first();
                    abort_unless($evidence && Storage::disk('local')->exists($locked->evidence_path), 422, 'Bukti pemeriksaan tidak tersedia.');
                    $evidence->update(['status' => $action === 'resolve' ? 'verified' : 'rejected', 'verified_by' => $request->user()->id, 'verified_at' => now(), 'review_notes' => $request->input('notes')]);
                    $locked->update(['status' => $action === 'resolve' ? 'resolved' : 'revision_requested', 'effectiveness' => $request->input('notes'), 'reviewed_by' => $request->user()->id]);
                }
                $locked->increment('version');
                AuditEvent::record($locked, 'spip.'.$action, ['notes' => $request->input('notes'), 'evidence_path' => $path ? 'private' : null], $locked->asset->room?->unit_id);
            });
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            } throw $exception;
        }

        return back()->with('success', 'Tindak lanjut SPIP tersimpan.');
    }
}
