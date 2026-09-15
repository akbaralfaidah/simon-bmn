<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\InventorySession;
use App\Models\InventoryItem;
use App\Models\InventoryFinding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'start_date' => 'required|date',
            'asset_ids' => 'required|array',
            'asset_ids.*' => 'exists:assets,id'
        ]);

        DB::transaction(function() use ($validated, $request) {
            $session = InventorySession::create([
                'name' => $validated['name'],
                'start_date' => $validated['start_date'],
                'status' => 'active',
                'created_by' => $request->user()->id
            ]);
            
            foreach ($validated['asset_ids'] as $assetId) {
                InventoryItem::create([
                    'inventory_session_id' => $session->id,
                    'asset_id' => $assetId,
                    'status' => 'pending'
                ]);
            }
        });

        return back()->with('success', 'Sesi Inventarisasi berhasil dibuat.');
    }

    public function checkItem(Request $request, InventoryItem $item)
    {
        $validated = $request->validate([
            'status' => 'required|in:found,missing,damaged',
            'notes' => 'nullable|string',
            'finding_description' => 'required_if:status,missing,damaged'
        ]);
        
        DB::transaction(function() use ($validated, $request, $item) {
            $item->update([
                'status' => $validated['status'],
                'notes' => $validated['notes'],
                'checked_by' => $request->user()->id
            ]);
            
            if (in_array($validated['status'], ['missing', 'damaged'])) {
                InventoryFinding::updateOrCreate(
                    ['inventory_item_id' => $item->id],
                    [
                        'description' => $validated['finding_description'],
                        'status' => 'open'
                    ]
                );
            } else {
                $item->finding()->delete();
            }
        });
        
        return back()->with('success', 'Pengecekan item berhasil dicatat.');
    }

    public function closeSession(Request $request, InventorySession $session)
    {
        $session->update([
            'status' => 'closed',
            'end_date' => today()
        ]);
        
        return back()->with('success', 'Sesi Inventarisasi telah ditutup.');
    }
}
