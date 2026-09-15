<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\LoanRequest;
use App\Models\LoanItem;
use App\Models\Reservation;
use App\Models\Bast;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class LoanController extends Controller
{
    public function create()
    {
        return Inertia::render('Loans/Create', [
            'availableAssets' => Asset::where('status', 'active')->where('is_loanable', true)->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'purpose' => 'required|string',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'asset_ids' => 'required|array|min:1',
            'asset_ids.*' => 'exists:assets,id'
        ]);

        DB::transaction(function () use ($validated, $request) {
            $loan = LoanRequest::create([
                'user_id' => $request->user()->id,
                'purpose' => $validated['purpose'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'status' => 'pending_approval'
            ]);

            foreach ($validated['asset_ids'] as $assetId) {
                LoanItem::create([
                    'loan_request_id' => $loan->id,
                    'asset_id' => $assetId,
                    'status' => 'pending'
                ]);
            }
        });

        return redirect()->route('dashboard')->with('success', 'Pengajuan pinjaman berhasil dibuat dan menunggu persetujuan Koordinator.');
    }

    public function approvals()
    {
        $loans = LoanRequest::with(['user', 'items.asset'])
            ->where('status', 'pending_approval')
            ->latest()
            ->paginate(15);
            
        return Inertia::render('Loans/Approvals', [
            'loans' => $loans
        ]);
    }

    public function approve(Request $request, LoanRequest $loan)
    {
        if ($loan->status !== 'pending_approval') {
            return back()->with('error', 'Status pengajuan tidak valid.');
        }

        DB::beginTransaction();
        try {
            // Lock assets
            $assetIds = $loan->items()->pluck('asset_id')->toArray();
            Asset::whereIn('id', $assetIds)->lockForUpdate()->get();

            $hasConflict = false;
            foreach ($loan->items as $item) {
                $conflict = Reservation::where('asset_id', $item->asset_id)
                    ->where('status', 'active')
                    ->where(function ($query) use ($loan) {
                        $query->whereBetween('start_date', [$loan->start_date, $loan->end_date])
                              ->orWhereBetween('end_date', [$loan->start_date, $loan->end_date])
                              ->orWhere(function ($q) use ($loan) {
                                  $q->where('start_date', '<=', $loan->start_date)
                                    ->where('end_date', '>=', $loan->end_date);
                              });
                    })->exists();
                    
                if ($conflict) {
                    $hasConflict = true;
                    break;
                }
            }

            if ($hasConflict) {
                DB::rollBack();
                $loan->update(['status' => 'rejected']);
                return back()->with('error', 'Gagal menyetujui. Terdapat bentrok jadwal pada aset yang diajukan.');
            }

            // Create reservations
            foreach ($loan->items as $item) {
                $item->update(['status' => 'approved']);
                Reservation::create([
                    'asset_id' => $item->asset_id,
                    'loan_item_id' => $item->id,
                    'start_date' => $loan->start_date,
                    'end_date' => $loan->end_date,
                    'status' => 'active'
                ]);
            }

            $loan->update([
                'status' => 'approved',
                'coordinator_id' => $request->user()->id
            ]);
            
            // Generate Draft BAST
            $bastNumber = 'BAST-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
            Bast::create([
                'bast_number' => $bastNumber,
                'bast_type' => 'loan',
                'reference_id' => $loan->id,
                'reference_type' => LoanRequest::class,
                'status' => 'draft',
                'issued_by' => $request->user()->id,
                'received_by' => $loan->user_id
            ]);

            DB::commit();
            return back()->with('success', 'Pinjaman disetujui. Reservasi terkunci dan BAST Draf dibuat.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    public function printBast(Bast $bast)
    {
        $bast->load(['reference.user', 'issuer', 'reference.items.asset']);
        $pdf = Pdf::loadView('pdf.bast', ['bast' => $bast]);
        return $pdf->stream("{$bast->bast_number}.pdf");
    }

    public function return(Request $request, LoanRequest $loan)
    {
        if ($loan->status !== 'active') return back()->with('error', 'Status pinjaman tidak aktif.');

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:loan_items,id',
            'items.*.condition' => 'required|in:Baik,Rusak Ringan,Rusak Berat'
        ]);

        DB::transaction(function() use ($loan, $validated, $request) {
            foreach ($validated['items'] as $inputItem) {
                $item = LoanItem::find($inputItem['id']);
                
                $item->asset->update(['condition' => $inputItem['condition']]);
                
                if ($item->reservation) {
                    $item->reservation->update(['status' => 'fulfilled']);
                }

                $item->update(['status' => 'returned']);
            }
            
            $allReturned = $loan->items()->where('status', '!=', 'returned')->count() === 0;
            if ($allReturned) {
                $loan->update(['status' => 'completed']);
            }
            
            $bastNumber = 'BAST-RET-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
            Bast::create([
                'bast_number' => $bastNumber,
                'bast_type' => 'return',
                'reference_id' => $loan->id,
                'reference_type' => LoanRequest::class,
                'status' => 'signed',
                'issued_by' => $loan->user_id,
                'received_by' => $request->user()->id
            ]);
        });

        return back()->with('success', 'Aset berhasil dikembalikan.');
    }
}
