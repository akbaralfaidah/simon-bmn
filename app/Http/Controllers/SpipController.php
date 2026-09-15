<?php

namespace App\Http\Controllers;

use App\Models\SpipRecord;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SpipController extends Controller
{
    public function index()
    {
        $records = SpipRecord::with(['asset', 'assessor'])->latest()->paginate(15);
        return Inertia::render('Spip/Index', ['records' => $records]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'period' => 'required|string',
            'asset_id' => 'nullable|exists:assets,id',
            'risk_description' => 'required|string',
            'control_action' => 'required|string'
        ]);

        SpipRecord::create([
            'period' => $validated['period'],
            'asset_id' => $validated['asset_id'] ?? null,
            'risk_description' => $validated['risk_description'],
            'control_action' => $validated['control_action'],
            'status' => 'open',
            'assessor_id' => $request->user()->id
        ]);

        return back()->with('success', 'Rekaman SPIP BMN berhasil ditambahkan.');
    }
}
