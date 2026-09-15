<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\InvitationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\InvitationExportController;
use App\Http\Controllers\Admin\EventDesignController;
use App\Http\Controllers\Staff\ScannerController;
use App\Http\Controllers\Staff\CheckInController;

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'admin',])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('events', EventController::class);
    Route::post('/events/{event}/invitations/generate', [InvitationController::class, 'generate'])->name('events.invitations.generate');
    Route::get('/events/{event}/invitations', [InvitationController::class, 'index'])->name('events.invitations.index');
    Route::post('/events/{event}/export/qr', [InvitationExportController::class, 'qrOnly'])->name('events.export.qr');
    Route::post('/events/{event}/exports/qr/start', [InvitationExportController::class, 'start',])
        ->name('events.exports.qr.start');
    Route::post('/events/{event}/exports/{export}/process', [InvitationExportController::class, 'process',])
        ->name('events.exports.process');
    Route::get('/events/{event}/exports/{export}/download', [InvitationExportController::class, 'download',])
        ->name('events.exports.download');
    Route::get('/events/{event}/design', [EventDesignController::class, 'edit',])->name('events.design.edit');
    Route::post('/events/{event}/design/background', [EventDesignController::class, 'uploadBackground',])
        ->name('events.design.background');
    Route::put('/events/{event}/design', [EventDesignController::class, 'update',])->name('events.design.update');
    Route::post('/events/{event}/exports/design/start', [InvitationExportController::class, 'startDesign',])
        ->name('events.exports.design.start');
    Route::post('/events/{event}/exports/{export}/design/process', [InvitationExportController::class, 'processDesign',])->name('events.exports.design.process');
    Route::patch('/events/{event}/invitations/{invitation}/mark-scanned',[InvitationController::class,'markAsScanned'])->name('events.invitations.mark-scanned');
    Route::patch('/events/{event}/invitations/{invitation}/reset',[InvitationController::class,'reset'])
    ->name('events.invitations.reset');
    Route::patch('/events/{event}/invitations/{invitation}/set-limit',[InvitationController::class,'setLimit',])
    ->name('events.invitations.set-limit');
});

Route::middleware(['auth','scanner',])->prefix('staff')->name('staff.')->group(function () {
        Route::get('/scanner',[ScannerController::class, 'index'])->name('scanner');
        Route::post('/check-in',[CheckInController::class, 'store'])->name('check-in');
});


require __DIR__ . '/auth.php';
