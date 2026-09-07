<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\InvitationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth','admin',])->prefix('admin')->name('admin.')->group(function () {
        Route::resource(
            'events',
            EventController::class
        );
        Route::post(
            '/events/{event}/invitations/generate',
            [InvitationController::class, 'generate']
        )->name('events.invitations.generate');

        Route::get(
            '/events/{event}/invitations',
            [InvitationController::class, 'index']
        )->name(
            'events.invitations.index'
        );
    });


require __DIR__.'/auth.php';
