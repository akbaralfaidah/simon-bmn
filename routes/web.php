<?php

use App\Http\Controllers\AdministrationController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetIdentityController;
use App\Http\Controllers\AssetPlacementController;
use App\Http\Controllers\CustodyController;
use App\Http\Controllers\DisposalController;
use App\Http\Controllers\DocumentCenterController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SpipController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => to_route(auth()->check() ? 'dashboard' : 'login'));
Route::get('/help', fn () => Inertia::render('Help'))->name('help');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/security/sessions', [SessionController::class, 'index'])->name('sessions.index');
    Route::post('/security/sessions/revoke', [SessionController::class, 'revoke'])->middleware('throttle:6,1')->name('sessions.revoke');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/profile/signature', [ProfileController::class, 'signature'])->name('profile.signature');
    Route::post('/profile/signature', [ProfileController::class, 'updateSignature'])->middleware('throttle:10,1')->name('profile.signature.update');
    Route::delete('/profile/signature', [ProfileController::class, 'destroySignature'])->middleware('throttle:10,1')->name('profile.signature.destroy');
});

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/security/two-factor', [TwoFactorController::class, 'show'])->name('two-factor.show');
    foreach (['enable', 'confirm', 'challenge', 'recovery', 'disable'] as $action) {
        Route::post('/security/two-factor/'.$action, [TwoFactorController::class, $action])->middleware('throttle:6,1')->name('two-factor.'.$action);
    }
});

Route::middleware(['auth', 'active', 'verified'])->group(function () {
    Route::get('/documents', [DocumentCenterController::class, 'index'])->name('documents.index');
    Route::post('/documents', [DocumentCenterController::class, 'store'])->name('documents.store');
    Route::get('/documents/{document}', [DocumentCenterController::class, 'show'])->name('documents.show');
    Route::post('/documents/{document}/{action}', [DocumentCenterController::class, 'action'])->name('documents.action');
    Route::get('/dashboard', [WorkspaceController::class, 'dashboard'])->name('dashboard');
    Route::resource('assets', AssetController::class);
    Route::get('/assets/{asset}/qrcode', [QrCodeController::class, 'show'])->name('assets.qrcode');
    Route::get('/media/{media}', [MediaController::class, 'show'])->name('media.show');
    Route::post('/evidence/photos', [MediaController::class, 'storeEvidence'])->middleware('throttle:20,1')->name('media.evidence');
    Route::post('/media/{media}/retry', [MediaController::class, 'retry'])->middleware('throttle:6,1')->name('media.retry');
    Route::post('/media/{media}/replace', [MediaController::class, 'replace'])->middleware('throttle:6,1')->name('media.replace');

    Route::get('/loans', [LoanController::class, 'index'])->name('loans.index');
    Route::get('/loans/create', [LoanController::class, 'create'])->name('loans.create');
    Route::get('/loans/availability', [LoanController::class, 'availability'])->middleware('throttle:60,1')->name('loans.availability');
    Route::post('/loans', [LoanController::class, 'store'])->middleware('throttle:20,1')->name('loans.store');
    Route::get('/loans/approvals', [LoanController::class, 'index'])->name('loans.approvals');
    Route::get('/loans/{loan}', [LoanController::class, 'show'])->name('loans.show');
    Route::get('/loans/{loan}/edit', [LoanController::class, 'edit'])->name('loans.edit');
    Route::post('/loans/{loan}/draft', [LoanController::class, 'update'])->name('loans.update');
    Route::post('/loans/{loan}/approve', [LoanController::class, 'approve'])->name('loans.approve');
    Route::post('/loans/{loan}/return', [LoanController::class, 'return'])->name('loans.return');
    Route::post('/loans/{loan}/actions/{action}', [LoanController::class, 'action'])->name('loans.action');
    Route::get('/basts/{bast}/print', [LoanController::class, 'printBast'])->name('basts.print');
    Route::get('/basts/{bast}/download', [LoanController::class, 'document'])->defaults('action', 'download')->name('basts.download');
    Route::post('/basts/{bast}/sign', [LoanController::class, 'signBast'])->middleware('throttle:20,1')->name('basts.sign');
    Route::post('/basts/{bast}/{action}', [LoanController::class, 'document'])->whereIn('action', ['upload', 'verify'])->name('basts.action');

    Route::post('/assets/{asset}/assign', [CustodyController::class, 'assign'])->name('custody.assign');
    Route::post('/custody/{assignment}/revoke', [CustodyController::class, 'revoke'])->name('custody.revoke');
    Route::post('/custody/{assignment}/actions/{action}', [CustodyController::class, 'action'])->name('custody.action');
    Route::get('/custody/{assignment}/evidence/{type}', [CustodyController::class, 'evidence'])->name('custody.evidence');
    Route::post('/inventory/sessions', [InventoryController::class, 'store'])->name('inventory.store');
    Route::get('/inventory/sessions/{session}', [InventoryController::class, 'show'])->name('inventory.show');
    Route::post('/inventory/items/{item}/check', [InventoryController::class, 'checkItem'])->name('inventory.check');
    Route::post('/inventory/sessions/{session}/close', [InventoryController::class, 'closeSession'])->name('inventory.close');
    Route::post('/inventory/sessions/{session}/reviews/{action}', [InventoryController::class, 'review'])->name('inventory.review');
    Route::get('/inventory/sessions/{session}/evidence/{review}', [InventoryController::class, 'evidence'])->whereNumber('review')->name('inventory.evidence');
    Route::post('/maintenance', [MaintenanceController::class, 'store'])->name('maintenance.store');
    Route::post('/maintenance/{maintenance}/complete', [MaintenanceController::class, 'complete'])->name('maintenance.complete');
    Route::post('/assets/{asset}/disposal', [DisposalController::class, 'propose'])->name('disposal.propose');
    Route::post('/disposals/{disposal}/approve', [DisposalController::class, 'approve'])->name('disposal.approve');
    Route::get('/spip', [SpipController::class, 'index'])->name('spip.index');
    Route::post('/spip', [SpipController::class, 'store'])->name('spip.store');
    Route::post('/spip/{record}/{action}', [SpipController::class, 'action'])->name('spip.action');
    Route::get('/workspace/{module}', [WorkspaceController::class, 'index'])->name('workspace.index');
    Route::post('/workspace/{module}', [WorkspaceController::class, 'store'])->name('workspace.store');
    Route::post('/records/{record}/actions/{action}', [WorkspaceController::class, 'recordAction'])->name('records.action');
    Route::post('/identity-transitions', [AssetIdentityController::class, 'store'])->middleware('throttle:20,1')->name('identities.store');
    Route::post('/identity-transitions/{record}/apply', [AssetIdentityController::class, 'apply'])->name('identities.apply');
    Route::get('/identity-transitions/{record}/evidence', [AssetIdentityController::class, 'evidence'])->name('identities.evidence');
    Route::post('/notifications/{notification}/read', [WorkspaceController::class, 'notification'])->name('notifications.read');
    Route::get('/reports/assets/export', [WorkspaceController::class, 'export'])->name('reports.export');
    Route::get('/reports/snapshots', [ReportController::class, 'index'])->name('reports.snapshots');
    Route::post('/reports/snapshots', [ReportController::class, 'store'])->name('reports.store');
    Route::get('/reports/snapshots/{report}/{format}', [ReportController::class, 'download'])->name('reports.download');
    Route::get('/administration', [AdministrationController::class, 'index'])->name('administration.index');
    Route::get('/administration/placement', [AssetPlacementController::class, 'index'])->name('placement.index');
    Route::post('/administration/placement/{asset}', [AssetPlacementController::class, 'store'])->name('placement.store');
    Route::post('/administration/users/{user}', [AdministrationController::class, 'user'])->name('administration.user');
    Route::delete('/administration/users/{user}', [AdministrationController::class, 'destroyUser'])->name('administration.user.destroy');
    Route::post('/administration/assignments/{assignment}/update', [AdministrationController::class, 'updateAssignment'])->name('administration.assignment.update');
    Route::post('/administration/assignments/{assignment}/revoke', [AdministrationController::class, 'revokeAssignment'])->name('administration.assignment.revoke');
    Route::post('/administration/organization/{kind}', [AdministrationController::class, 'organization'])->name('administration.organization');
    Route::get('/imports', [ImportController::class, 'index'])->name('imports.index');
    Route::post('/imports', [ImportController::class, 'store'])->name('imports.store');
    Route::post('/imports/{batch}/commit', [ImportController::class, 'commit'])->name('imports.commit');
    Route::post('/imports/{batch}/rows/{row}', [ImportController::class, 'correct'])->name('imports.correct');
});

Route::get('/pending', function () {
    if (auth()->user()->status === 'active') {
        return to_route('dashboard');
    }

    return Inertia::render('Auth/Pending');
})->middleware('auth')->name('pending.notice');

require __DIR__.'/auth.php';
