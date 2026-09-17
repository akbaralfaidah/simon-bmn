<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAssetRequest;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AuditEvent;
use App\Models\Room;
use App\Services\AccessScope;
use App\Services\AssetIdentityService;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AssetController extends Controller
{
    public function __construct(private AccessScope $scope) {}

    public function index(Request $request): Response
    {
        $filters = $request->validate(['search' => 'nullable|string|max:150', 'condition' => 'nullable|in:Baik,Rusak Ringan,Rusak Berat', 'page' => 'nullable|integer|min:1']);
        $query = $this->scope->assets($request->user())->with(['category', 'room', 'media']);
        if ($search = $filters['search'] ?? null) {
            $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('nup', 'like', "%{$search}%")->orWhere('item_code', 'like', "%{$search}%"));
        }
        if ($condition = $filters['condition'] ?? null) {
            $query->where('condition', $condition);
        }

        return Inertia::render('Assets/Index', [
            'assets' => $query->latest()->paginate(20)->withQueryString()->through(fn (Asset $asset) => $this->present($asset, $request)),
            'filters' => $filters,
            'canCreate' => $request->user()->can('create', Asset::class),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Asset::class);

        return Inertia::render('Assets/Create', $this->options($request));
    }

    public function store(StoreAssetRequest $request, MediaService $mediaService): RedirectResponse
    {
        $createdMedia = [];
        try {
            DB::transaction(function () use ($request, $mediaService, &$createdMedia) {
                app(AssetIdentityService::class)->lock();
                app(AssetIdentityService::class)->assertAvailable($request->validated());
                $asset = Asset::create($request->safe()->except(['images', 'version']));
                foreach ($request->file('images', []) as $image) {
                    $createdMedia[] = $mediaService->processAndSaveImage($image, $asset, 'photo');
                }
                AuditEvent::record($asset, 'asset.created', [], $asset->room?->unit_id);
            });
        } catch (\Throwable $exception) {
            $mediaService->cleanup($createdMedia);
            throw $exception;
        }

        return to_route('assets.index')->with('success', 'Aset berhasil didaftarkan.');
    }

    public function show(Request $request, Asset $asset): Response
    {
        $this->authorize('view', $asset);

        return Inertia::render('Assets/Show', ['asset' => $this->present($asset->load(['category', 'room', 'media', 'identifiers']), $request), 'canEdit' => $request->user()->can('update', $asset)]);
    }

    public function edit(Request $request, Asset $asset): Response
    {
        $this->authorize('update', $asset);

        return Inertia::render('Assets/Edit', ['asset' => $asset->load('media'), ...$this->options($request)]);
    }

    public function update(StoreAssetRequest $request, Asset $asset, MediaService $mediaService): RedirectResponse
    {
        $createdMedia = [];
        try {
            DB::transaction(function () use ($request, $asset, $mediaService, &$createdMedia) {
                $locked = Asset::whereKey($asset->id)->lockForUpdate()->firstOrFail();
                $this->authorize('update', $locked);
                if ($request->filled('version') && $request->integer('version') !== $locked->version) {
                    throw ValidationException::withMessages(['version' => 'Aset telah diubah pengguna lain. Muat ulang sebelum menyimpan.']);
                }
                if ($locked->room_id != $request->integer('room_id')) {
                    throw ValidationException::withMessages(['room_id' => 'Perubahan ruangan harus melalui proses mutasi.']);
                }
                foreach (['satker_code', 'item_code', 'nup'] as $field) {
                    if ($request->exists($field) && (string) $request->input($field) !== (string) $locked->$field) {
                        throw ValidationException::withMessages([$field => 'Perubahan identitas harus melalui register ASP/PSP dengan bukti dan review independen.']);
                    }
                }
                $locked->fill($request->safe()->except(['images', 'version']))->increment('version');
                $locked->save();
                foreach ($request->file('images', []) as $image) {
                    $createdMedia[] = $mediaService->processAndSaveImage($image, $locked, 'photo');
                }
                AuditEvent::record($locked, 'asset.updated', [], $locked->room?->unit_id);
            });
        } catch (\Throwable $exception) {
            $mediaService->cleanup($createdMedia);
            throw $exception;
        }

        return to_route('assets.show', $asset)->with('success', 'Aset berhasil diperbarui.');
    }

    public function destroy(Asset $asset): never
    {
        $this->authorize('delete', $asset);
        abort(403, 'Gunakan proses penghapusan BMN agar riwayat tetap tersimpan.');
    }

    private function options(Request $request): array
    {
        return ['categories' => AssetCategory::orderBy('name')->get(), 'rooms' => Room::whereIn('id', $this->scope->roomIds($request->user(), true, AccessScope::COORDINATORS))->orderBy('name')->get()];
    }

    private function present(Asset $asset, Request $request): array
    {
        $data = $asset->toArray();
        if (! $this->scope->coordinate($request->user(), $asset) && ! $this->scope->inspect($request->user(), $asset)) {
            unset($data['value'], $data['acquisition_source']);
        }

        return $data;
    }
}
