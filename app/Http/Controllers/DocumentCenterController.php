<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\Bast;
use App\Models\CustodyAssignment;
use App\Models\InventorySession;
use App\Models\LoanRequest;
use App\Models\SpipRecord;
use App\Models\WorkRecord;
use App\Services\AccessScope;
use App\Services\DocumentService;
use App\Services\UploadScanner;
use App\Services\WordTemplateService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DocumentCenterController extends Controller
{
    public function __construct(private DocumentService $documents, private AccessScope $scope) {}

    public function index(Request $request): Response
    {
        $assetIds = $this->scope->assets($request->user(), true)->select('id');
        $query = Bast::query()->where(function ($query) use ($assetIds, $request) {
            $query->whereHasMorph('reference', [LoanRequest::class, CustodyAssignment::class], fn ($parent) => $parent->where('user_id', $request->user()->id))
                ->orWhereHasMorph('reference', [WorkRecord::class], fn ($parent) => $parent->where('created_by', $request->user()->id))
                ->orWhereHasMorph('reference', [CustodyAssignment::class, WorkRecord::class, SpipRecord::class], fn ($query) => $query->whereIn('asset_id', $assetIds))
                ->orWhereHasMorph('reference', [LoanRequest::class, InventorySession::class], fn ($query) => $query->whereHas('items')->whereDoesntHave('items', fn ($items) => $items->whereNotIn('asset_id', $assetIds)));
        });
        $request->validate(['spip' => 'nullable|integer|min:1']);
        if ($request->filled('spip')) {
            $query->where('reference_type', SpipRecord::class)->where('reference_id', $request->integer('spip'));
        }
        $records = $query->latest()->paginate(20)->withQueryString()->through(fn ($document) => [...$document->makeHidden(['signed_pdf_path', 'generated_pdf_path', 'checksum', 'snapshot'])->toArray(), 'canVerify' => $this->documents->canVerify($request->user(), $document), 'canUpload' => in_array($request->user()->id, [$document->issued_by, $document->received_by]), 'canView' => $this->documents->canView($request->user(), $document->reference)]);

        return Inertia::render('Operations/Documents', ['documents' => $records, 'templates' => DocumentService::TEMPLATES, 'templatesApproved' => config('simon.templates_approved')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['parent_type' => 'required|in:custody,inventory,record', 'parent_id' => 'required|integer', 'template' => ['required', Rule::in(array_keys(DocumentService::TEMPLATES))]]);
        abort_unless($request->user()->hasRole([...AccessScope::COORDINATORS, ...AccessScope::KEEPERS]), 403);
        $class = match ($data['parent_type']) {
            'custody' => CustodyAssignment::class, 'inventory' => InventorySession::class, default => WorkRecord::class
        };
        $this->documents->create($class::findOrFail($data['parent_id']), $request->user(), $data['template']);

        return to_route('documents.index')->with('success', 'Draf snapshot dibuat. Template belum disahkan tetap berlabel draf.');
    }

    public function show(Request $request, Bast $document): mixed
    {
        abort_unless($this->documents->canView($request->user(), $document->reference), 404);
        if ($request->boolean('signed')) {
            abort_unless($document->signed_pdf_path && $document->scan_status === 'clean' && Storage::disk('local')->exists($document->signed_pdf_path), 404);
            AuditEvent::record($document, 'document.downloaded');

            return Storage::disk('local')->download($document->signed_pdf_path, $document->bast_number.'.pdf', ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
        }

        if ($document->reference instanceof LoanRequest) {
            $path = app(WordTemplateService::class)->generateBastDocument($document);

            return response()->download($path, $document->bast_number.'-draf.docx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])->deleteFileAfterSend(true);
        }

        return Pdf::setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false])->loadView('pdf.bast', ['bast' => $document])->stream($document->bast_number.'-draf.pdf');
    }

    public function action(Request $request, Bast $document, string $action): RedirectResponse
    {
        abort_unless($this->documents->canView($request->user(), $document->reference), 404);
        abort_if($document->reference instanceof SpipRecord, 422, 'Bukti SPIP ditinjau dan direvisi melalui halaman SPIP.');
        abort_unless(in_array($action, ['upload', 'verify', 'reject', 'replace']), 404);
        $path = null;
        if ($action === 'upload') {
            abort_unless(in_array($request->user()->id, [$document->issued_by, $document->received_by]), 403);
            $request->validate(['document' => 'required|file|mimes:pdf|max:10240']);
            app(UploadScanner::class)->assertClean($request->file('document')->getRealPath());
            $path = $request->file('document')->store('documents/signed', 'local');
            abort_unless($path, 503);
        } else {
            abort_unless($this->documents->canVerify($request->user(), $document) || ($action === 'replace' && $document->issued_by === $request->user()->id), 403);
            if ($action !== 'verify') {
                $request->validate(['reason' => 'required|string|max:5000']);
            }
        }
        try {
            DB::transaction(function () use ($request, $document, $action, $path) {
                $locked = Bast::whereKey($document->id)->lockForUpdate()->firstOrFail();
                if ($action === 'upload') {
                    abort_unless($locked->status === 'draft', 422, 'Berkas tidak dapat ditimpa. Buat versi pengganti.');
                    $locked->update(['status' => 'uploaded', 'scan_status' => 'clean', 'signed_pdf_path' => $path, 'checksum' => hash_file('sha256', Storage::disk('local')->path($path))]);
                } elseif ($action === 'replace') {
                    abort_unless(in_array($locked->status, ['rejected', 'verified']), 422);
                    abort_if(Bast::where('supersedes_id', $locked->id)->exists(), 422, 'Versi pengganti sudah ada.');
                    $this->documents->create($locked->reference, $request->user(), $locked->bast_type, $locked, $request->input('reason'));
                } else {
                    abort_unless($locked->status === 'uploaded' && $locked->scan_status === 'clean' && $locked->signed_pdf_path && Storage::disk('local')->exists($locked->signed_pdf_path), 422, 'Berkas belum lolos pemeriksaan atau tidak tersedia.');
                    $locked->update(['status' => $action === 'verify' ? 'verified' : 'rejected', 'verified_by' => $request->user()->id, 'verified_at' => now(), 'review_notes' => $request->input('reason')]);
                }
                AuditEvent::record($locked, 'document.'.$action);
            }, 3);
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            } throw $exception;
        }

        return back()->with('success', 'Dokumen diperbarui; versi lama tetap disimpan.');
    }
}
