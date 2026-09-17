<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetLocationHistory;
use App\Models\AuditEvent;
use App\Models\Room;
use App\Services\AccessScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AssetPlacementController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizePlacement($request);

        return Inertia::render('Operations/Placement', [
            'assets' => Asset::whereNull('room_id')->orderBy('name')->paginate(25),
            'rooms' => Room::with('unit:id,name')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, Asset $asset): RedirectResponse
    {
        $this->authorizePlacement($request);
        $data = $request->validate(['room_id' => 'required|exists:rooms,id', 'version' => 'required|integer|min:1', 'reason' => 'required|string|max:5000']);
        DB::transaction(function () use ($request, $asset, $data) {
            $room = Room::whereKey($data['room_id'])->lockForUpdate()->firstOrFail();
            $asset = Asset::whereKey($asset->id)->lockForUpdate()->firstOrFail();
            abort_unless($asset->room_id === null && $asset->version === (int) $data['version'], 422, 'Aset sudah ditempatkan atau diubah. Muat ulang halaman.');
            abort_if($asset->occupancies()->where('is_active', true)->exists(), 422, 'Selesaikan penguasaan aktif sebelum penempatan awal.');
            AssetLocationHistory::create(['asset_id' => $asset->id, 'from_room_id' => null, 'to_room_id' => $room->id, 'user_id' => $request->user()->id, 'notes' => $data['reason']]);
            $asset->update(['room_id' => $room->id, 'version' => $asset->version + 1]);
            AuditEvent::record($asset, 'asset.initial_placement', ['room_id' => $room->id, 'reason' => $data['reason']], $room->unit_id);
        });

        return back()->with('success', 'Penempatan awal dicatat. Identitas dan riwayat aset tetap dipertahankan.');
    }

    private function authorizePlacement(Request $request): void
    {
        $scope = app(AccessScope::class);
        abort_unless($scope->global($request->user()) && $scope->administer($request->user()), 403);
    }
}
