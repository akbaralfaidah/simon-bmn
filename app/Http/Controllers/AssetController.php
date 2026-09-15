<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Room;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $query = Asset::with(['category', 'room', 'media']);

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('nup', 'like', "%{$search}%")
                  ->orWhere('item_code', 'like', "%{$search}%");
        }

        $assets = $query->latest('acquisition_date')->paginate(20)->withQueryString();

        return Inertia::render('Assets/Index', [
            'assets' => $assets,
            'filters' => $request->only(['search'])
        ]);
    }

    public function create()
    {
        return Inertia::render('Assets/Create', [
            'categories' => AssetCategory::all(),
            'rooms' => Room::all(),
        ]);
    }

    public function store(Request $request, MediaService $mediaService)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:asset_categories,id',
            'room_id' => 'nullable|exists:rooms,id',
            'nup' => 'nullable|string|max:255',
            'item_code' => 'nullable|string|max:255',
            'brand_type' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'specification' => 'nullable|string',
            'acquisition_date' => 'nullable|date',
            'value' => 'nullable|numeric',
            'condition' => 'required|string|in:Baik,Rusak Ringan,Rusak Berat',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
        ]);

        DB::transaction(function () use ($validated, $request, $mediaService) {
            $asset = Asset::create($validated);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $mediaService->processAndSaveImage($image, $asset, 'photo');
                }
            }
        });

        return redirect()->route('assets.index')->with('success', 'Aset berhasil didaftarkan.');
    }
}
