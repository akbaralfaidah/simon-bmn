<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Disposal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DisposalController extends Controller
{
    public function propose(Request $request, Asset $asset)
    {
        $request->validate([
            'reason' => 'required|string'
        ]);

        Disposal::create([
            'asset_id' => $asset->id,
            'reason' => $request->reason,
            'status' => 'proposed',
            'proposed_by' => $request->user()->id
        ]);

        return back()->with('success', 'Usulan penghapusan BMN berhasil diajukan.');
    }

    public function approve(Request $request, Disposal $disposal)
    {
        // AC-21: Finalisasi penghapusan tanpa SK ditolak
        $request->validate([
            'sk_number' => 'required|string'
        ]);

        DB::transaction(function() use ($request, $disposal) {
            $disposal->update([
                'sk_number' => $request->sk_number,
                'status' => 'approved',
                'approved_by' => $request->user()->id
            ]);

            $disposal->asset->update([
                'status' => 'disposed'
            ]);
        });

        return back()->with('success', 'Penghapusan BMN disetujui (SK tervalidasi) dan aset diarsipkan.');
    }
}
