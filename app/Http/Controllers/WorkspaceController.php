<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWorkRecordRequest;
use App\Models\Asset;
use App\Models\AssetLocationHistory;
use App\Models\AssetOccupancy;
use App\Models\AuditEvent;
use App\Models\Bast;
use App\Models\CustodyAssignment;
use App\Models\Disposal;
use App\Models\InventorySession;
use App\Models\LoanRequest;
use App\Models\MaintenanceLog;
use App\Models\OrganizationUnit;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\SpipRecord;
use App\Models\User;
use App\Models\WorkRecord;
use App\Services\AccessScope;
use App\Services\MediaService;
use App\Services\UploadScanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WorkspaceController extends Controller
{
    public const MODULES = [
        'custody' => 'Penetapan pemegang', 'inventory' => 'Inventarisasi & DBR', 'maintenance' => 'Perawatan',
        'disposals' => 'Usulan penghapusan', 'spip' => 'Pengendalian SPIP', 'transfers' => 'Mutasi ruangan',
        'incidents' => 'Laporan kerusakan & kehilangan', 'registers' => 'Register ASP / PSP',
        'documents' => 'Dokumen serah-terima', 'reports' => 'Laporan aset', 'audit' => 'Jejak audit', 'notifications' => 'Notifikasi',
    ];

    public function __construct(private AccessScope $scope) {}

    public function dashboard(Request $request): Response
    {
        $assets = $this->scope->assets($request->user());
        $rooms = $this->scope->roomIds($request->user(), true, AccessScope::COORDINATORS);

        return Inertia::render('Dashboard', [
            'stats' => ['total' => (clone $assets)->count(), 'kondisi_baik' => (clone $assets)->where('condition', 'Baik')->count(), 'kondisi_rusak' => (clone $assets)->where('condition', '!=', 'Baik')->count(),
                'my_loans' => LoanRequest::where('user_id', $request->user()->id)->whereIn('status', ['active', 'approved', 'returning'])->count(),
                'approvals' => LoanRequest::where('status', 'pending_approval')->where('user_id', '!=', $request->user()->id)->whereHas('items')->whereDoesntHave('items.asset', fn ($query) => $query->whereNull('room_id')->orWhereNotIn('room_id', $rooms))->count()],
            'recentLoans' => LoanRequest::where('user_id', $request->user()->id)->latest()->limit(5)->get(['id', 'purpose', 'start_date', 'end_date', 'status']),
        ]);
    }

    public function index(Request $request, string $module): Response
    {
        abort_unless(array_key_exists($module, self::MODULES), 404);
        $operational = $request->user()->hasRole([...AccessScope::COORDINATORS, ...AccessScope::KEEPERS]);
        if (in_array($module, ['inventory', 'maintenance', 'disposals', 'spip', 'transfers', 'registers', 'reports', 'audit'])) {
            abort_unless($operational, 403);
        }
        $assetQuery = $this->scope->assets($request->user(), true);
        $assetIds = (clone $assetQuery)->select('id');
        $query = match ($module) {
            'custody' => CustodyAssignment::with(['asset:id,name,room_id', 'user:id,name'])->where(fn ($query) => $query->where('user_id', $request->user()->id)->orWhereIn('asset_id', $assetIds)),
            'inventory' => InventorySession::withCount('items')->whereHas('items')->whereDoesntHave('items', fn ($query) => $query->whereNotIn('asset_id', $assetIds)),
            'maintenance' => MaintenanceLog::with(['asset:id,name,room_id', 'loanItem.request:id,user_id', 'custodyAssignment:id,user_id'])->whereIn('asset_id', $assetIds),
            'disposals' => Disposal::with('asset:id,name,room_id')->whereIn('asset_id', $assetIds),
            'spip' => SpipRecord::with('asset:id,name,room_id')->whereIn('asset_id', $assetIds),
            'documents' => Bast::where('reference_type', LoanRequest::class)->whereHasMorph('reference', [LoanRequest::class], function ($query) use ($assetIds, $request) {
                $query->where('user_id', $request->user()->id)->orWhere(fn ($query) => $query->whereHas('items')->whereDoesntHave('items', fn ($query) => $query->whereNotIn('asset_id', $assetIds)));
            }),
            'reports' => $assetQuery->with(['room:id,name', 'category:id,name']),
            'audit' => AuditEvent::query()->when(! $this->scope->global($request->user()), fn ($query) => $query->where(function ($query) use ($request, $assetIds) {
                $query->where('user_id', $request->user()->id)
                    ->orWhereIn('unit_id', $this->scope->assignments($request->user(), AccessScope::COORDINATORS)->whereNull('room_id')->select('unit_id'))
                    ->orWhere(fn ($query) => $query->where('subject_type', Asset::class)->whereIn('subject_id', $assetIds))
                    ->orWhere(fn ($query) => $query->where('subject_type', WorkRecord::class)->whereIn('subject_id', WorkRecord::whereIn('asset_id', $assetIds)->select('id')));
            })),
            'notifications' => $request->user()->notifications(),
            default => WorkRecord::with(['asset:id,name,room_id', 'media'])->where('kind', $module)->where(fn ($query) => $query->where('created_by', $request->user()->id)->orWhereIn('asset_id', $assetIds)->orWhereIn('data->target_room_id', $this->scope->roomIds($request->user(), true, AccessScope::KEEPERS))->when($module === 'incidents', fn ($query) => $query->orWhere('data->reported_for_user_id', $request->user()->id))),
        };
        $request->validate(['search' => 'nullable|string|max:150']);
        if ($request->filled('search') && in_array($module, ['reports', 'incidents', 'transfers', 'registers'])) {
            $query->where($module === 'reports' ? 'name' : 'title', 'like', '%'.$request->input('search').'%');
        }
        $records = $query->latest()->paginate(20)->withQueryString()->through(function ($record) use ($request) {
            $data = $record->makeHidden(['evidence_path', 'signed_pdf_path', 'generated_pdf_path', 'checksum'])->toArray();
            if ($record instanceof WorkRecord) {
                unset($data['data']['document_path'], $data['data']['document_checksum']);
                $isBorrower = (int) ($record->data['reported_for_user_id'] ?? 0) === $request->user()->id;
                $data['canReview'] = ! $isBorrower && $record->created_by !== $request->user()->id && $record->asset && $this->scope->coordinate($request->user(), $record->asset);
                $data['canInspectFollowup'] = ! $isBorrower && $record->asset && $this->scope->inspect($request->user(), $record->asset);
                $data['canResolve'] = ! $isBorrower && $record->asset && $this->scope->coordinate($request->user(), $record->asset) && (int) ($record->data['checked_by'] ?? 0) !== $request->user()->id;
                $data['canStartMaintenance'] = ! $isBorrower && $record->asset && $this->scope->coordinate($request->user(), $record->asset);
            }
            if ($record instanceof MaintenanceLog) {
                $data['canInspectMaintenance'] = $this->scope->inspect($request->user(), $record->asset) && $record->loanItem?->request?->user_id !== $request->user()->id && $record->custodyAssignment?->user_id !== $request->user()->id;
                unset($data['loan_item'], $data['custody_assignment']);
            }
            if ($record instanceof CustodyAssignment) {
                $data['hasReturnEvidence'] = (bool) $record->return_evidence_path;
                $data['hasHandoverEvidence'] = (bool) $record->evidence_path;
            }

            return $data;
        });
        $assets = $this->scope->assets($request->user(), $module !== 'incidents')->orderBy('name')->get(['id', 'name', 'room_id', 'condition', 'status']);
        $units = $this->scope->global($request->user()) ? OrganizationUnit::query() : OrganizationUnit::whereIn('id', $this->scope->unitIds($request->user()));

        return Inertia::render('Operations/Index', [
            'module' => $module, 'title' => self::MODULES[$module], 'records' => $records,
            'assets' => $assets, 'rooms' => Room::whereIn('id', $this->scope->roomIds($request->user(), true))->orderBy('name')->get(['id', 'name', 'unit_id']),
            'users' => $module === 'custody' ? User::where('status', 'active')->whereHas('profile', fn ($query) => $query->whereIn('unit_id', (clone $units)->select('id')))->get(['id', 'name']) : [],
            'filters' => $request->only('search'),
        ]);
    }

    public function store(StoreWorkRecordRequest $request, string $module): RedirectResponse
    {
        $data = $request->validated();
        $photos = [];
        try {
            DB::transaction(function () use ($request, $module, $data, &$photos) {
                $asset = Asset::whereKey($data['asset_id'])->lockForUpdate()->firstOrFail();
                if ($module === 'transfers') {
                    abort_if((int) $data['target_room_id'] === (int) $asset->room_id, 422, 'Pilih ruangan tujuan yang berbeda.');
                }
                $record = WorkRecord::create([
                    'kind' => $module, 'title' => $data['title'], 'status' => 'submitted',
                    'unit_id' => $asset->room?->unit_id, 'asset_id' => $asset->id, 'created_by' => $request->user()->id,
                    'data' => collect($data)->except(['title', 'asset_id', 'images'])->all(),
                ]);
                foreach ($request->file('images', []) as $file) {
                    $photos[] = app(MediaService::class)->processAndSaveImage($file, $record, 'evidence');
                }
                AuditEvent::record($record, $module.'.submitted', [], $record->unit_id);
            });
        } catch (\Throwable $exception) {
            app(MediaService::class)->cleanup($photos);
            throw $exception;
        }

        return back()->with('success', 'Catatan disimpan dan menunggu tindak lanjut.');
    }

    public function recordAction(Request $request, WorkRecord $record, string $action): RedirectResponse
    {
        abort_unless(in_array($action, ['approve', 'reject', 'handover', 'receive', 'inspect-resolution', 'resolve']), 404);
        $request->validate(['notes' => 'required|string|max:5000', 'version' => 'required|integer|min:1']);
        $path = null;
        if ($record->kind === 'transfers' && $action === 'handover') {
            abort_unless($this->scope->inspect($request->user(), $record->asset), 403);
            $request->validate(['document' => 'required|file|mimes:pdf|max:10240']);
            app(UploadScanner::class)->assertClean($request->file('document')->getRealPath());
            $path = $request->file('document')->store('documents/transfers', 'local');
            abort_unless($path, 503);
        }
        try {
            DB::transaction(function () use ($request, $record, $action, $path) {
                $asset = Asset::whereKey($record->asset_id)->lockForUpdate()->firstOrFail();
                $locked = WorkRecord::whereKey($record->id)->lockForUpdate()->firstOrFail();
                abort_unless($locked->version === $request->integer('version'), 422, 'Catatan sudah berubah. Muat ulang terlebih dahulu.');
                $data = $locked->data;
                abort_if($locked->kind === 'incidents' && (int) ($data['reported_for_user_id'] ?? 0) === $request->user()->id, 403, 'Tindak lanjut pinjaman sendiri harus ditangani petugas lain.');
                if ($action === 'approve' || $action === 'reject') {
                    $this->authorize('update', $asset);
                    abort_if($locked->created_by === $request->user()->id, 403, 'Catatan sendiri harus ditinjau koordinator lain.');
                    abort_unless($locked->status === 'submitted', 422);
                    if ($locked->kind === 'registers' && $action === 'approve') {
                        $request->validate(['mapping_verified' => 'required|accepted']);
                        abort_unless(isset($data['before_identity'], $data['after_identity']) && ($data['document_scan_status'] ?? '') === 'clean', 422, 'Register lama perlu diajukan ulang dengan pemetaan dan bukti lengkap.');
                        abort_unless($asset->version === (int) $data['asset_version'], 422, 'Aset berubah sejak pengajuan. Ajukan ulang dengan data terbaru.');
                        $data['mapping_status'] = 'verified';
                        $data['verified_at'] = now()->toIso8601String();
                    }
                    abort_if($action === 'reject' && $locked->kind === 'incidents' && (isset($data['loan_item_id']) || isset($data['custody_assignment_id'])), 422, 'Temuan pengembalian harus ditindaklanjuti dan diperiksa sebelum ditutup.');
                    if ($locked->kind === 'transfers' && $action === 'approve') {
                        abort_if($asset->status !== 'active' || $asset->occupancies()->where('is_active', true)->exists() || Reservation::where('asset_id', $asset->id)->where('status', 'active')->exists(), 422, 'Aset sedang dipakai atau direservasi.');
                        abort_unless(in_array((int) $data['target_room_id'], $this->scope->roomIds($request->user(), true, AccessScope::COORDINATORS)), 403);
                        $data['from_room_id'] = $asset->room_id;
                        AssetOccupancy::create(['asset_id' => $asset->id, 'user_id' => $request->user()->id, 'occupancy_type' => 'transfer', 'starts_at' => now(), 'is_active' => true]);
                    }
                    $locked->status = $action === 'approve' ? 'approved' : 'rejected';
                    $data['reviewed_by'] = $request->user()->id;
                } elseif ($action === 'handover') {
                    abort_unless($locked->kind === 'transfers' && $locked->status === 'approved' && $this->scope->inspect($request->user(), $asset), 403);
                    abort_if(WorkRecord::where('kind', 'incidents')->where('asset_id', $asset->id)->whereIn('status', ['submitted', 'approved'])->exists(), 422, 'Selesaikan insiden barang sebelum serah-terima mutasi.');
                    $locked->status = 'in_transit';
                    $data['document_path'] = $path;
                    $data['handed_by'] = $request->user()->id;
                } elseif ($action === 'receive') {
                    abort_unless($locked->kind === 'transfers' && $locked->status === 'in_transit', 422);
                    abort_unless(in_array((int) $data['target_room_id'], $this->scope->roomIds($request->user(), true, AccessScope::KEEPERS)) && $data['handed_by'] !== $request->user()->id, 403);
                    AssetLocationHistory::create(['asset_id' => $asset->id, 'from_room_id' => $asset->room_id, 'to_room_id' => $data['target_room_id'], 'user_id' => $request->user()->id, 'notes' => $request->input('notes')]);
                    $asset->update(['room_id' => $data['target_room_id'], 'version' => $asset->version + 1]);
                    AssetOccupancy::where('asset_id', $asset->id)->where('occupancy_type', 'transfer')->where('is_active', true)->update(['is_active' => false, 'ends_at' => now()]);
                    $locked->status = 'completed';
                    $data['received_by'] = $request->user()->id;
                } elseif ($action === 'inspect-resolution') {
                    abort_unless($locked->kind === 'incidents' && $locked->status === 'approved' && $this->scope->inspect($request->user(), $asset), 403);
                    abort_if(MaintenanceLog::where('incident_id', $locked->id)->where('status', 'in_progress')->exists(), 422, 'Selesaikan pemeriksaan perawatan terlebih dahulu.');
                    $request->validate(['condition' => 'required|in:Baik,Rusak Ringan,Rusak Berat']);
                    $data['checked_by'] = $request->user()->id;
                    $data['checked_condition'] = $request->input('condition');
                    $data['checked_notes'] = $request->input('notes');
                    $data['checked_at'] = now()->toIso8601String();
                } else {
                    abort_unless($locked->kind === 'incidents' && $locked->status === 'approved', 422, 'Gunakan penerapan transisi terverifikasi untuk register ASP/PSP.');
                    $this->authorize('update', $asset);
                    if ($locked->kind === 'incidents') {
                        abort_if(MaintenanceLog::where('incident_id', $locked->id)->where('status', 'in_progress')->exists(), 422, 'Perawatan masih berlangsung.');
                        abort_if($locked->media()->where(fn ($query) => $query->where('processing_status', '!=', 'ready')->orWhere('scan_status', '!=', 'clean'))->exists(), 422, 'Semua foto bukti harus lolos pemeriksaan sebelum insiden ditutup.');
                        abort_unless(isset($data['checked_by']) && $data['checked_by'] !== $request->user()->id, 422, 'PJ lain harus memeriksa hasil tindak lanjut sebelum penutupan insiden.');
                        $asset->update(['condition' => $data['checked_condition'], 'version' => $asset->version + 1]);
                    }
                    $locked->status = 'resolved';
                    $data['resolution'] = $request->input('notes');
                }
                $locked->data = $data;
                $locked->version++;
                $locked->save();
                AuditEvent::record($locked, $locked->kind.'.'.$action, ['notes' => $request->input('notes')], $locked->unit_id);
            });
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        return back()->with('success', 'Tindak lanjut tercatat.');
    }

    public function notification(Request $request, string $notification): RedirectResponse
    {
        $request->user()->notifications()->whereKey($notification)->firstOrFail()->markAsRead();

        return back();
    }

    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()->hasRole([...AccessScope::COORDINATORS, ...AccessScope::KEEPERS]), 403);
        $assets = $this->scope->assets($request->user(), true)->with('room')->orderBy('name');

        return response()->streamDownload(function () use ($assets) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['ID', 'Nama', 'Kode Barang', 'NUP', 'Ruangan', 'Kondisi', 'Status', 'Nilai'], ',', '"', '');
            foreach ($assets->cursor() as $asset) {
                $row = [$asset->id, $asset->name, $asset->item_code, $asset->nup, $asset->room?->name, $asset->condition, $asset->status, $asset->value];
                fputcsv($stream, array_map(fn ($value) => preg_match('/^[=+@\-\t\r\n]/', (string) $value) ? "'".$value : $value, $row), ',', '"', '');
            }
            fclose($stream);
        }, 'laporan-aset-'.today()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }
}
