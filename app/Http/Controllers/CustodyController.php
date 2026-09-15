<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\CustodyAssignment;
use App\Models\CustodyEvent;
use App\Models\Bast;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustodyController extends Controller
{
    public function assign(Request $request, Asset $asset)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'start_date' => 'required|date',
            'notes' => 'nullable|string'
        ]);

        // AC-17: Pencegahan kepemilikan ganda
        $activeAssignment = CustodyAssignment::where('asset_id', $asset->id)
            ->where('status', 'active')
            ->first();
            
        if ($activeAssignment) {
            return back()->with('error', 'Aset ini masih memiliki penetapan pemegang aktif. Harap tarik (revoke) terlebih dahulu.');
        }

        DB::transaction(function() use ($request, $asset) {
            $assignment = CustodyAssignment::create([
                'asset_id' => $asset->id,
                'user_id' => $request->user_id,
                'start_date' => $request->start_date,
                'notes' => $request->notes,
                'status' => 'active'
            ]);

            CustodyEvent::create([
                'custody_assignment_id' => $assignment->id,
                'type' => 'assigned',
                'notes' => 'Penetapan awal pemegang aset.',
                'performed_by' => $request->user()->id
            ]);
            
            // Generate BAST Penetapan
            $bastNumber = 'BAST-PNT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
            Bast::create([
                'bast_number' => $bastNumber,
                'bast_type' => 'assignment',
                'reference_id' => $assignment->id,
                'reference_type' => CustodyAssignment::class,
                'status' => 'signed',
                'issued_by' => $request->user()->id,
                'received_by' => $request->user_id
            ]);
        });

        return back()->with('success', 'Penetapan pemegang aset berhasil dicatat.');
    }

    public function revoke(Request $request, CustodyAssignment $assignment)
    {
        if ($assignment->status !== 'active') return back()->with('error', 'Penetapan sudah tidak aktif.');
        
        DB::transaction(function() use ($request, $assignment) {
            $assignment->update([
                'status' => 'revoked',
                'end_date' => today()
            ]);
            
            CustodyEvent::create([
                'custody_assignment_id' => $assignment->id,
                'type' => 'revoked',
                'notes' => 'Pencabutan / penarikan aset dari pemegang.',
                'performed_by' => $request->user()->id
            ]);
        });
        
        return back()->with('success', 'Penetapan aset berhasil ditarik.');
    }
}
