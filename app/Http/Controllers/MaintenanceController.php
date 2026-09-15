<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\MaintenanceLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaintenanceController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'description' => 'required|string',
            'cost' => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date'
        ]);

        DB::transaction(function() use ($validated, $request) {
            MaintenanceLog::create([
                'asset_id' => $validated['asset_id'],
                'description' => $validated['description'],
                'cost' => $validated['cost'] ?? 0,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'] ?? null,
                'status' => empty($validated['end_date']) ? 'in_progress' : 'completed',
                'reported_by' => $request->user()->id
            ]);

            if (!empty($validated['end_date'])) {
                Asset::where('id', $validated['asset_id'])->update(['condition' => 'Baik']);
            }
        });

        return back()->with('success', 'Catatan perawatan berhasil disimpan.');
    }
}
