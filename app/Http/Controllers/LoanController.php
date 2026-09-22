<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\Bast;
use App\Models\LoanRequest;
use App\Models\Reservation;
use App\Models\User;
use App\Models\WorkRecord;
use App\Services\AccessScope;
use App\Services\UploadScanner;
use App\Services\WordTemplateService;
use App\Services\WorkflowService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LoanController extends Controller
{
    public function __construct(private AccessScope $scope, private WorkflowService $workflow) {}

    public function index(Request $request): Response
    {
        $rooms = $this->scope->roomIds($request->user(), true);
        $loans = LoanRequest::query()->where(function ($query) use ($request, $rooms) {
            $query->where('user_id', $request->user()->id)->orWhere(function ($query) use ($rooms) {
                $query->whereHas('items')->whereDoesntHave('items.asset', fn ($assets) => $assets->whereNull('room_id')->orWhereNotIn('room_id', $rooms));
            });
        })->with(['user:id,name', 'items.asset:id,name,room_id']);
        if ($request->routeIs('loans.approvals')) {
            abort_unless($request->user()->hasRole([...AccessScope::COORDINATORS, ...AccessScope::KEEPERS]), 403);
            $approverRooms = $this->scope->roomIds($request->user(), true, [...AccessScope::COORDINATORS, ...AccessScope::KEEPERS]);
            $loans->where('status', 'pending_approval')->where('user_id', '!=', $request->user()->id)
                ->whereDoesntHave('items.asset', fn ($query) => $query->whereNull('room_id')->orWhereNotIn('room_id', $approverRooms));
        }

        return Inertia::render('Loans/Index', ['loans' => $loans->latest()->paginate(15)->withQueryString(), 'approvalMode' => $request->routeIs('loans.approvals')]);
    }

    private function getStrictlyAvailableAssetsQuery(User $user, int|string|null $exceptLoanId = null)
    {
        return $this->scope->assets($user)
            ->where('status', 'active')
            ->where('condition', 'Baik')
            ->where('is_loanable', true)
            ->whereDoesntHave('occupancies', function ($query) {
                $query->where('is_active', true);
            })
            ->whereDoesntHave('loanItems', function ($query) use ($exceptLoanId) {
                $query->whereNotIn('status', ['returned', 'cancelled', 'rejected']);
                if ($exceptLoanId) {
                    $query->where('loan_request_id', '!=', $exceptLoanId);
                }
                $query->whereHas('request', function ($q) {
                    $q->whereNotIn('status', ['cancelled', 'rejected', 'completed']);
                });
            })
            ->whereDoesntHave('reservations', function ($query) use ($exceptLoanId) {
                $query->where('status', 'active');
                if ($exceptLoanId) {
                    $query->where(function ($sq) use ($exceptLoanId) {
                        $sq->whereDoesntHave('loanItem')
                            ->orWhereHas('loanItem', fn ($liq) => $liq->where('loan_request_id', '!=', $exceptLoanId));
                    });
                }
            })
            ->whereDoesntHave('workRecords', function ($query) {
                $query->where('kind', 'incidents')
                    ->whereIn('status', ['submitted', 'approved']);
            });
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Loans/Create', [
            'submissionKey' => (string) Str::uuid(),
            'availableAssets' => $this->getStrictlyAvailableAssetsQuery($request->user())
                ->orderBy('name')
                ->get(['id', 'name', 'nup', 'item_code', 'brand_type', 'condition']),
        ]);
    }

    public function availability(Request $request): JsonResponse
    {
        $data = $request->validate([
            'asset_ids' => 'required|array|min:1|max:30',
            'asset_ids.*' => 'required|uuid|distinct',
            'start_date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'loan_id' => 'nullable|integer',
        ]);
        abort_if(Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date'])) > 92, 422, 'Pemeriksaan jadwal maksimal 93 hari.');
        $assets = $this->scope->assets($request->user())->whereKey($data['asset_ids'])->get();
        abort_unless($assets->count() === count($data['asset_ids']), 403);
        $exceptLoanId = $data['loan_id'] ?? null;
        $result = $assets->mapWithKeys(function ($asset) use ($data, $exceptLoanId) {
            $reasons = [];
            if ($asset->status !== 'active' || ! $asset->is_loanable || $asset->condition !== 'Baik') {
                $reasons[] = 'Tidak layak / tidak diizinkan dipinjam.';
            }
            if ($asset->occupancies()->where('is_active', true)->exists()) {
                $reasons[] = 'Masih dikuasai atau dalam proses serah-terima/perawatan.';
            }
            if (WorkRecord::where('kind', 'incidents')->where('asset_id', $asset->id)->whereIn('status', ['submitted', 'approved'])->exists()) {
                $reasons[] = 'Tindak lanjut kejadian belum selesai.';
            }
            if ($asset->loanItems()
                ->whereNotIn('status', ['returned', 'cancelled', 'rejected'])
                ->when($exceptLoanId, fn ($q) => $q->where('loan_request_id', '!=', $exceptLoanId))
                ->whereHas('request', fn ($q) => $q->whereNotIn('status', ['cancelled', 'rejected', 'completed']))
                ->exists()) {
                $reasons[] = 'Sedang dalam proses pengajuan atau peminjaman aktif.';
            }
            $reservations = Reservation::where('asset_id', $asset->id)
                ->where('status', 'active')
                ->when($exceptLoanId, function ($q) use ($exceptLoanId) {
                    $q->whereHas('loanItem', fn ($liq) => $liq->where('loan_request_id', '!=', $exceptLoanId));
                })
                ->where('start_date', '<=', $data['end_date'].' 23:59:59')
                ->where('end_date', '>=', $data['start_date'].' 00:00:00')
                ->get(['start_date', 'end_date']);
            if ($reservations->isNotEmpty()) {
                $reasons[] = 'Ada jadwal yang bertumpuk.';
            }

            return [$asset->id => ['available' => $reasons === [], 'reasons' => $reasons, 'reservations' => $reservations]];
        });

        return response()->json(['assets' => $result, 'checked_at' => now()->toIso8601String()])->header('Cache-Control', 'private, no-store');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'purpose' => 'required|string|max:5000', 'start_date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'asset_ids' => 'required|array|min:1|max:30', 'asset_ids.*' => 'required|uuid|distinct',
            'submission_key' => 'nullable|uuid', 'draft' => 'sometimes|boolean',
        ]);
        $loan = DB::transaction(function () use ($request, $data) {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            if (! empty($data['submission_key'])) {
                $existing = LoanRequest::where('submission_key', $data['submission_key'])->first();
                if ($existing) {
                    abort_unless($existing->user_id === $request->user()->id, 403);
                    abort_if($existing->submission_hash && ! hash_equals($existing->submission_hash, hash('sha256', json_encode($data))), 422, 'Kunci pengajuan sudah digunakan untuk isian berbeda. Muat formulir baru.');

                    return $existing;
                }
            }
            $assets = $this->getStrictlyAvailableAssetsQuery($request->user())
                ->whereKey($data['asset_ids'])
                ->get();
            if ($assets->count() !== count($data['asset_ids'])) {
                throw ValidationException::withMessages(['asset_ids' => 'Ada aset yang tidak tersedia atau sedang dalam peminjaman/proses pengajuan lain.']);
            }
            $loan = LoanRequest::create([
                'user_id' => $request->user()->id, 'purpose' => $data['purpose'], 'start_date' => $data['start_date'],
                'end_date' => $data['end_date'], 'status' => $request->boolean('draft') ? 'draft' : 'pending_approval',
                'submission_key' => $data['submission_key'] ?? null,
                'submission_hash' => hash('sha256', json_encode($data)),
            ]);
            foreach ($assets as $asset) {
                $loan->items()->create(['asset_id' => $asset->id, 'status' => 'pending']);
            }
            AuditEvent::record($loan, 'loan.created');

            return $loan;
        });

        return to_route('loans.show', $loan)->with('success', 'Pengajuan berhasil disimpan.');
    }

    public function show(Request $request, LoanRequest $loan): Response
    {
        $loan->load(['user:id,name', 'items.asset:id,name,nup,item_code,room_id,condition', 'items.media', 'coordinator:id,name']);
        abort_unless($this->scope->viewLoan($request->user(), $loan), 403);

        return Inertia::render('Loans/Show', [
            'loan' => $loan,
            'returnFollowups' => WorkRecord::where('kind', 'incidents')->where('data->loan_request_id', $loan->id)->whereIn('asset_id', $loan->items->pluck('asset_id'))->get()->map(fn (WorkRecord $record) => ['id' => $record->id, 'item_id' => $record->data['loan_item_id'], 'status' => $record->status]),
            'documents' => Bast::whereMorphedTo('reference', $loan)->get()->makeHidden(['signed_pdf_path', 'generated_pdf_path', 'checksum']),
            'canDecide' => $this->scope->decideLoan($request->user(), $loan),
            'canInspect' => $loan->user_id !== $request->user()->id && $loan->items->contains(fn ($item) => $this->scope->inspect($request->user(), $item->asset)),
        ]);
    }

    public function edit(Request $request, LoanRequest $loan): Response
    {
        abort_unless($loan->user_id === $request->user()->id && in_array($loan->status, ['draft', 'revision_requested']), 403);

        return Inertia::render('Loans/Create', [
            'submissionKey' => $loan->submission_key,
            'initialLoan' => [...$loan->toArray(), 'asset_ids' => $loan->items()->pluck('asset_id')],
            'availableAssets' => $this->getStrictlyAvailableAssetsQuery($request->user(), $loan->id)
                ->orderBy('name')
                ->get(['id', 'name', 'nup', 'item_code', 'brand_type', 'condition']),
        ]);
    }

    public function update(Request $request, LoanRequest $loan): RedirectResponse
    {
        abort_unless($loan->user_id === $request->user()->id, 403);
        $data = $request->validate(['purpose' => 'required|string|max:5000', 'start_date' => 'required|date_format:Y-m-d|after_or_equal:today', 'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date', 'asset_ids' => 'required|array|min:1|max:30', 'asset_ids.*' => 'required|uuid|distinct', 'version' => 'required|integer|min:1', 'draft' => 'required|boolean']);
        DB::transaction(function () use ($request, $loan, $data) {
            $locked = LoanRequest::whereKey($loan->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($locked->status, ['draft', 'revision_requested']) && $locked->version === $data['version'], 422, 'Pengajuan sudah berubah atau diproses. Muat ulang.');
            $assets = $this->getStrictlyAvailableAssetsQuery($request->user(), $locked->id)
                ->whereKey($data['asset_ids'])
                ->get();
            abort_unless($assets->count() === count($data['asset_ids']), 422, 'Barang tidak tersedia atau sedang dalam peminjaman/proses pengajuan lain.');
            AuditEvent::record($locked, 'loan.draft_revised', ['previous' => $locked->only(['purpose', 'start_date', 'end_date']), 'asset_ids_before' => $locked->items()->pluck('asset_id')->all()]);
            $locked->items()->whereNotIn('asset_id', $data['asset_ids'])->delete();
            foreach ($assets as $asset) {
                $locked->items()->firstOrCreate(['asset_id' => $asset->id], ['status' => 'pending']);
            }
            $locked->update(['purpose' => $data['purpose'], 'start_date' => $data['start_date'], 'end_date' => $data['end_date'], 'version' => $locked->version + 1, 'status' => $data['draft'] ? 'draft' : 'pending_approval']);
        });

        return to_route('loans.show', $loan)->with('success', 'Revisi pengajuan tersimpan.');
    }

    public function approve(Request $request, LoanRequest $loan): RedirectResponse
    {
        $this->workflow->transition($loan, $request->user(), 'approve');

        return back()->with('success', 'Disetujui. Jadwal telah direservasi; lanjutkan persiapan dan dokumen.');
    }

    public function action(Request $request, LoanRequest $loan, string $action): RedirectResponse
    {
        abort_unless(in_array($action, ['submit', 'reject', 'revise', 'cancel', 'cancel-item', 'prepare', 'handover', 'accept', 'request-return', 'inspect', 'close', 'extend', 'approve-extension', 'reject-extension']), 404);
        if ($action === 'submit') {
            DB::transaction(function () use ($loan, $request) {
                $locked = LoanRequest::whereKey($loan->id)->lockForUpdate()->firstOrFail();
                abort_unless($locked->user_id === $request->user()->id && $locked->status === 'draft', 403);
                abort_if(Carbon::parse($locked->start_date)->lt(today()), 422, 'Tanggal mulai draf sudah lewat. Batalkan draf dan buat pengajuan dengan jadwal baru.');
                $locked->update(['status' => 'pending_approval']);
                AuditEvent::record($locked, 'loan.submitted');
            });
        } else {
            $data = $request->validate([
                'reason' => [Rule::requiredIf(in_array($action, ['reject', 'revise', 'cancel', 'cancel-item', 'extend', 'reject-extension'])), 'nullable', 'string', 'max:5000'],
                'item_ids' => [Rule::requiredIf(in_array($action, ['cancel-item', 'prepare', 'handover', 'accept', 'request-return', 'inspect', 'close'])), 'array', 'min:1', 'max:30'],
                'item_ids.*' => 'required|integer|distinct',
                'notes' => 'nullable|string|max:5000',
                'condition' => [Rule::requiredIf($action === 'inspect'), Rule::in(['Baik', 'Rusak Ringan', 'Rusak Berat'])],
                'completeness' => [Rule::requiredIf($action === 'inspect'), Rule::in(['complete', 'incomplete'])],
                'end_date' => [Rule::requiredIf($action === 'extend'), 'date_format:Y-m-d', 'after:today'],
            ]);
            $this->workflow->transition($loan, $request->user(), $action, $data);
        }

        return back()->with('success', 'Proses berhasil disimpan.');
    }

    public function return(Request $request, LoanRequest $loan): RedirectResponse
    {
        $data = $request->validate(['item_ids' => 'required|array|min:1|max:30', 'item_ids.*' => 'required|integer|distinct', 'notes' => 'nullable|string|max:5000']);
        $this->workflow->transition($loan, $request->user(), 'request-return', $data);

        return back()->with('success', 'Pengembalian diajukan. Tunggu pemeriksaan PJ ruangan.');
    }

    public function printBast(Request $request, Bast $bast): BinaryFileResponse
    {
        abort_unless($bast->reference instanceof LoanRequest && $this->scope->viewLoan($request->user(), $bast->reference), 403);

        $path = app(WordTemplateService::class)->generateBastDocument($bast);

        return response()->download($path, $bast->bast_number.'.docx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    public function document(Request $request, Bast $bast, string $action): mixed
    {
        abort_unless($bast->reference instanceof LoanRequest && $this->scope->viewLoan($request->user(), $bast->reference), 403);
        if ($action === 'download') {
            if ($bast->signed_pdf_path) {
                abort_unless($bast->scan_status === 'clean' && Storage::disk('local')->exists($bast->signed_pdf_path), 404);

                return Storage::disk('local')->download($bast->signed_pdf_path, $bast->bast_number.'-bertanda-tangan.pdf', ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
            }

            abort_unless($bast->status === 'verified', 404);

            $path = app(WordTemplateService::class)->generateBastDocument($bast);

            return response()->download($path, $bast->bast_number.'-bertanda-tangan.docx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])->deleteFileAfterSend(true);
        }
        abort_unless(in_array($action, ['upload', 'verify']), 404);
        $path = null;
        if ($action === 'upload') {
            abort_unless($bast->reference->user_id === $request->user()->id, 403);
            $request->validate(['document' => 'required|file|mimes:pdf|max:10240']);
            app(UploadScanner::class)->assertClean($request->file('document')->getRealPath());
            $path = $request->file('document')->store('documents/signed', 'local');
            abort_unless($path, 503);
        }
        try {
            DB::transaction(function () use ($request, $bast, $action, $path) {
                $locked = Bast::whereKey($bast->id)->lockForUpdate()->firstOrFail();
                if ($action === 'verify') {
                    abort_unless($this->scope->decideLoan($request->user(), $locked->reference), 403);
                    abort_unless($locked->status === 'uploaded' && $locked->scan_status === 'clean' && $locked->signed_pdf_path && Storage::disk('local')->exists($locked->signed_pdf_path), 422, 'Dokumen belum diunggah atau belum lolos pemeriksaan.');
                    $locked->update(['status' => 'verified', 'verified_by' => $request->user()->id, 'verified_at' => now()]);
                } else {
                    abort_unless($locked->status === 'draft', 422, 'Dokumen sudah diunggah; berkas tidak boleh ditimpa.');
                    $locked->update(['status' => 'uploaded', 'scan_status' => 'clean', 'signed_pdf_path' => $path, 'checksum' => hash_file('sha256', Storage::disk('local')->path($path))]);
                }
                AuditEvent::record($locked, 'document.'.$action);
            });
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        return back()->with('success', 'Dokumen berhasil diproses.');
    }

    public function signBast(Request $request, Bast $bast): RedirectResponse
    {
        abort_unless($bast->reference instanceof LoanRequest && $this->scope->viewLoan($request->user(), $bast->reference), 403);

        $data = $request->validate([
            'password' => ['required', 'current_password'],
            'role' => ['required', Rule::in(['peminjam', 'pj', 'koordinator'])],
        ]);

        $user = $request->user();
        $loan = $bast->reference;

        abort_unless(
            $user->profile?->signature_path && Storage::disk('local')->exists($user->profile->signature_path),
            422,
            'Anda belum memiliki tanda tangan tersimpan. Silakan unggah tanda tangan di menu Pengaturan Akun terlebih dahulu.'
        );

        $role = $data['role'];
        if ($role === 'peminjam') {
            abort_unless($loan->user_id === $user->id, 403, 'Hanya peminjam yang berhak menandatangani bagian ini.');
        } elseif ($role === 'pj') {
            abort_unless($loan->user_id !== $user->id && $loan->items->contains(fn ($item) => $this->scope->inspect($user, $item->asset)), 403, 'Hanya Penanggung Jawab Ruangan yang berhak menandatangani bagian ini.');
        } elseif ($role === 'koordinator') {
            abort_unless($this->scope->decideLoan($user, $loan), 403, 'Hanya Koordinator BMN yang berhak menandatangani bagian ini.');
        }

        DB::transaction(function () use ($bast, $user, $role) {
            $locked = Bast::whereKey($bast->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($locked->status, ['draft', 'uploaded']), 422, 'Dokumen BAST sudah berstatus final/terverifikasi.');

            $snapshot = $locked->snapshot ?? [];
            $signatures = data_get($snapshot, 'signatures', []);

            abort_if(! empty($signatures[$role]), 422, 'Bagian ini sudah Anda tanda tangani.');

            $signatures[$role] = [
                'user_id' => $user->id,
                'name' => $user->name,
                'nip' => $user->profile?->nip,
                'signed_at' => now()->toIso8601String(),
            ];

            $snapshot['signatures'] = $signatures;
            $locked->snapshot = $snapshot;

            $hasPeminjam = ! empty($signatures['peminjam']);
            $hasPj = ! empty($signatures['pj']);
            $hasKoor = ! empty($signatures['koordinator']);

            if ($hasPeminjam && $hasPj && $hasKoor) {
                $locked->status = 'verified';
                $locked->verified_by = $signatures['koordinator']['user_id'] ?? $user->id;
                $locked->verified_at = now();
                $locked->scan_status = 'clean';
            }

            $locked->save();

            AuditEvent::record($locked, 'document.signed', ['role' => $role, 'signer_id' => $user->id]);
        });

        return back()->with('success', 'Tanda tangan digital berhasil dibubuhkan pada BAST.');
    }
}
