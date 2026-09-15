<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified', 'active'])->name('dashboard');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    Route::resource('assets', \App\Http\Controllers\AssetController::class);
    
    // Loans
    Route::get('/loans/create', [\App\Http\Controllers\LoanController::class, 'create'])->name('loans.create');
    Route::post('/loans', [\App\Http\Controllers\LoanController::class, 'store'])->name('loans.store');
    Route::get('/loans/approvals', [\App\Http\Controllers\LoanController::class, 'approvals'])->name('loans.approvals');
    Route::post('/loans/{loan}/approve', [\App\Http\Controllers\LoanController::class, 'approve'])->name('loans.approve');
    Route::get('/basts/{bast}/print', [\App\Http\Controllers\LoanController::class, 'printBast'])->name('basts.print');
    Route::post('/loans/{loan}/return', [\App\Http\Controllers\LoanController::class, 'return'])->name('loans.return');
    
    // Custody (Penetapan Jangka Panjang)
    Route::post('/assets/{asset}/assign', [\App\Http\Controllers\CustodyController::class, 'assign'])->name('custody.assign');
    Route::post('/custody/{assignment}/revoke', [\App\Http\Controllers\CustodyController::class, 'revoke'])->name('custody.revoke');
    
    // QR Code BMN
    Route::get('/assets/{asset}/qrcode', [\App\Http\Controllers\QrCodeController::class, 'show'])->name('assets.qrcode');
});

Route::get('/pending', function () {
    if (auth()->check() && auth()->user()->status === 'active') {
        return redirect()->route('dashboard');
    }
    return Inertia::render('Auth/Pending');
})->middleware('auth')->name('pending.notice');

require __DIR__.'/auth.php';
