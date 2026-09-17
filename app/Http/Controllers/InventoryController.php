<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\InventoryFinding;
use App\Models\InventoryItem;
use App\Models\InventorySession;
use App\Services\AccessScope;
use App\Services\UploadScanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryController extends Controller
{
    public function __construct(private AccessScope $scope) {}

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'start_date' => 'required|date', 'asset_ids' => 'required|array|min:1|max:500', 'asset_ids.*' => 'required|uuid|distinct']);
        $assets = $this->scope->assets($request->user(), true)->whereKey($data['asset_ids'])->get();
        abort_unless($assets->count() === count($data['asset_ids']) && $assets->every(fn ($asset) => $this->scope->coordinate($request->user(), $asset)), 403);
        abort_unless($assets->pluck('room_id')->filter()->unique()->count() === 1 && ! $assets->contains('room_id', null), 422, 'Buat satu sesi per ruangan agar pemeriksaan dan pengesahan jelas.');
        $session = DB::transaction(function () use ($request, $data, $assets) {
            $session = InventorySession::create(['name' => $data['name'], 'start_date' => $data['start_date'], 'status' => 'draft', 'created_by' => $request->user()->id]);
            foreach ($assets as $asset) {
                $session->items()->create(['asset_id' => $asset->id, 'status' => 'pending', 'snapshot' => [...$asset->only(['id', 'name', 'nup', 'item_code', 'condition', 'room_id']), 'room' => $asset->room?->name]]);
            }
            AuditEvent::record($session, 'inventory.created');

            return $session;
        });

        return to_route('inventory.show', $session)->with('success', 'Draf sesi dibuat. Catat bukti disposisi sebelum pemeriksaan.');
    }

    public function show(Request $request, InventorySession $session): Response
    {
        $session->load(['items.asset:id,name,nup,room_id,condition', 'items.finding', 'items.media']);
        $this->authorizeSession($request, $session);

        $data = $session->toArray();
        $data['reviews'] = collect($session->reviews ?? [])->map(fn ($review, $index) => [...collect($review)->except(['path', 'checksum'])->all(), 'evidence_url' => isset($review['path']) ? route('inventory.evidence', [$session, $index]) : null])->all();

        return Inertia::render('Operations/Inventory', ['session' => $data,
            'canClose' => $session->items->every(fn ($item) => $this->scope->coordinate($request->user(), $item->asset)),
            'canCheck' => $session->items->every(fn ($item) => $this->scope->inspect($request->user(), $item->asset)),
        ]);
    }

    public function checkItem(Request $request, InventoryItem $item): RedirectResponse
    {
        abort_unless($this->scope->inspect($request->user(), $item->asset), 403);
        $data = $request->validate(['status' => 'required|in:found,missing,damaged,wrong_location,wrong_identity', 'notes' => 'nullable|string|max:5000', 'finding_description' => 'required_unless:status,found|nullable|string|max:5000']);
        DB::transaction(function () use ($request, $item, $data) {
            $session = InventorySession::whereKey($item->inventory_session_id)->lockForUpdate()->firstOrFail();
            abort_unless($session->status === 'active', 422, 'Sesi sudah ditutup.');
            $item = InventoryItem::whereKey($item->id)->lockForUpdate()->firstOrFail();
            $item->update(['status' => $data['status'], 'notes' => $data['notes'] ?? null, 'checked_by' => $request->user()->id]);
            if ($data['status'] !== 'found') {
                InventoryFinding::updateOrCreate(['inventory_item_id' => $item->id], ['description' => $data['finding_description'], 'status' => 'open']);
            } elseif ($item->finding) {
                $item->finding->update(['status' => 'resolved', 'action_taken' => $data['notes'] ?? 'Ditemukan saat pemeriksaan ulang.']);
            }
            AuditEvent::record($item, 'inventory.checked', $data, $item->asset->room?->unit_id);
            $session->increment('version');
        });

        return back()->with('success', 'Hasil pemeriksaan tercatat.');
    }

    public function closeSession(Request $request, InventorySession $session): RedirectResponse
    {
        DB::transaction(function () use ($request, $session) {
            $session = InventorySession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            $session->load('items.asset');
            $this->authorizeSession($request, $session);
            abort_unless($session->items->every(fn ($item) => $this->scope->coordinate($request->user(), $item->asset)), 403);
            if ($session->status !== 'authorized' || $session->items->contains('status', 'pending')) {
                throw ValidationException::withMessages(['workflow' => 'Selesaikan pemeriksaan dan seluruh tahap review/pengesahan sebelum menutup sesi.']);
            }
            $session->load('items.finding');
            $snapshot = $session->items->map(fn ($item) => [...$item->snapshot, 'inspection_status' => $item->status, 'notes' => $item->notes, 'checked_by' => $item->checked_by, 'finding' => $item->finding?->only(['description', 'status', 'action_taken'])])->all();
            $session->update(['status' => 'closed', 'end_date' => today(), 'final_snapshot' => ['items' => $snapshot, 'closed_by' => $request->user()->id, 'closed_at' => now()->toIso8601String()], 'version' => $session->version + 1]);
            AuditEvent::record($session, 'inventory.closed');
        });

        return back()->with('success', 'Sesi ditutup. Temuan tetap tersimpan untuk tindak lanjut.');
    }

    public function review(Request $request, InventorySession $session, string $action): RedirectResponse
    {
        $transitions = ['start' => ['draft', 'active'], 'submit' => ['active', 'sub_review'], 'sub-review' => ['sub_review', 'coordinator_review'], 'coordinate-review' => ['coordinator_review', 'head_review'], 'authorize' => ['head_review', 'authorized']];
        abort_unless(isset($transitions[$action]) || $action === 'revise', 404);
        $session->load('items.asset');
        $this->authorizeSession($request, $session);
        $isKeeper = $session->items->every(fn ($item) => $this->scope->inspect($request->user(), $item->asset));
        $isCoordinator = $session->items->every(fn ($item) => $this->scope->coordinate($request->user(), $item->asset));
        abort_unless($action === 'submit' ? $isKeeper : $isCoordinator, 403);
        $data = $request->validate(['version' => 'required|integer|min:1', 'notes' => 'required|string|max:5000']);
        $external = in_array($action, ['start', 'sub-review', 'authorize'], true);
        $path = null;
        $evidence = [];
        if ($external || $action === 'submit') {
            $evidence = $request->validate(['document' => 'required|file|mimes:pdf|max:10240', 'officer_name' => $external ? 'required|string|max:255' : 'nullable|string|max:255', 'reference_number' => 'required|string|max:255', 'signed_date' => 'required|date_format:Y-m-d|before_or_equal:today']);
            app(UploadScanner::class)->assertClean($request->file('document')->getRealPath());
            $path = $request->file('document')->store('documents/inventory-reviews', 'local');
            abort_unless($path, 503);
        }
        try {
            DB::transaction(function () use ($request, $session, $action, $transitions, $data, $evidence, $path) {
                $locked = InventorySession::whereKey($session->id)->lockForUpdate()->firstOrFail();
                $locked->load('items.asset');
                $this->authorizeSession($request, $locked);
                abort_unless($locked->version === $request->integer('version'), 422, 'Sesi sudah berubah. Muat ulang sebelum melanjutkan.');
                if ($action === 'revise') {
                    abort_unless(in_array($locked->status, ['sub_review', 'coordinator_review', 'head_review', 'authorized']), 422);
                    $next = 'active';
                } else {
                    abort_unless($locked->status === $transitions[$action][0], 422, 'Tahap review tidak sesuai.');
                    $next = $transitions[$action][1];
                }
                if ($action === 'submit') {
                    abort_if($locked->items->contains('status', 'pending'), 422, 'Periksa seluruh barang terlebih dahulu.');
                    abort_if($locked->items()->whereHas('media', fn ($query) => $query->whereNull('replacement_media_id')->where(fn ($status) => $status->where('processing_status', '!=', 'ready')->orWhere('scan_status', '!=', 'clean')))->exists(), 422, 'Tunggu pemeriksaan seluruh foto bukti sebelum mengirim kertas kerja.');
                }
                if ($action === 'coordinate-review') {
                    abort_if($locked->items->contains('checked_by', $request->user()->id), 403, 'Pemeriksa fisik tidak boleh mereview hasilnya sendiri.');
                }
                $entry = ['action' => $action, 'actor_id' => $request->user()->id, 'actor_name' => $request->user()->name, 'notes' => $data['notes'], 'at' => now()->toIso8601String()];
                if ($path) {
                    $entry = [...$entry, ...collect($evidence)->except('document')->all(), 'path' => $path, 'scan_status' => 'clean', 'checksum' => hash_file('sha256', Storage::disk('local')->path($path))];
                }
                $locked->update(['status' => $next, 'reviews' => [...($locked->reviews ?? []), $entry], 'version' => $locked->version + 1]);
                AuditEvent::record($locked, 'inventory.'.$action, collect($entry)->except(['path', 'checksum'])->all());
            }, 3);
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }

        return back()->with('success', 'Tahap inventarisasi tercatat. Bukti lama tetap diarsipkan.');
    }

    public function evidence(Request $request, InventorySession $session, int $review): StreamedResponse
    {
        $session->load('items.asset');
        $this->authorizeSession($request, $session);
        $entry = $session->reviews[$review] ?? [];
        abort_unless(isset($entry['path']) && ($entry['scan_status'] ?? '') === 'clean' && Storage::disk('local')->exists($entry['path']), 404);
        AuditEvent::record($session, 'inventory.evidence_downloaded', ['review' => $review]);

        return Storage::disk('local')->download($entry['path'], 'inventarisasi-'.$session->id.'-'.$review.'.pdf', ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    private function authorizeSession(Request $request, InventorySession $session): void
    {
        abort_unless($session->items->isNotEmpty() && $session->items->every(fn ($item) => $this->scope->coordinate($request->user(), $item->asset) || $this->scope->inspect($request->user(), $item->asset)), 403);
    }
}
