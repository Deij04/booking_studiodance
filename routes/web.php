<?php

use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\DanceClassController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\StudioController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LandingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('landing');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
    Route::get('/register', [AuthController::class, 'createRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

Route::get('/dashboard', function (Request $request) {
    return redirect()->route($request->user()->can('access-admin') ? 'admin.dashboard' : 'landing');
})
    ->middleware('auth')
    ->name('dashboard');

Route::middleware(['auth', 'can:access-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('bookings', BookingController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    Route::patch('bookings/{booking}/decision', [BookingController::class, 'decide'])->name('bookings.decide');
    Route::resource('users', UserController::class)->except('show');
    Route::resource('studios', StudioController::class)->except('show');
    Route::resource('dance-classes', DanceClassController::class)
        ->parameters(['dance-classes' => 'danceClass'])
        ->except('show');
});
