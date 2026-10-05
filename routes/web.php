<?php

use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\ClassRegistrationController;
use App\Http\Controllers\Admin\ClassScheduleController;
use App\Http\Controllers\Admin\DanceClassController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MembershipController;
use App\Http\Controllers\Admin\MembershipPlanController;
use App\Http\Controllers\Admin\StudioController;
use App\Http\Controllers\Admin\StudioOperationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\CustomerMembershipController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::view('/about-us', 'user.about-us')->name('user.about');
Route::get('/studios', [\App\Http\Controllers\User\StudioController::class, 'index'])->name('user.studios.index');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
    Route::get('/register', [AuthController::class, 'createRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');
Route::middleware('auth')->group(function () {
    Route::get('/memberships', [CustomerMembershipController::class, 'index'])->name('memberships.index');
    Route::post('/memberships', [CustomerMembershipController::class, 'store'])->name('memberships.store');
});

Route::get('/dashboard', function (Request $request) {
    return redirect()->route($request->user()->can('access-admin') ? 'admin.dashboard' : 'landing');
})
    ->middleware('auth')
    ->name('dashboard');

Route::middleware(['auth', 'can:access-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('memberships', MembershipController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    Route::patch('memberships/{membership}/decision', [MembershipController::class, 'decide'])->name('memberships.decide');
    Route::resource('membership-plans', MembershipPlanController::class)->only(['create', 'store', 'edit', 'update']);
    Route::resource('class-registrations', ClassRegistrationController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    Route::resource('class-schedules', ClassScheduleController::class)->only(['create', 'store', 'edit', 'update']);
    Route::patch('class-schedules/{classSchedule}/deactivate', [ClassScheduleController::class, 'deactivate'])->name('class-schedules.deactivate');
    Route::get('studio-operations', [StudioOperationController::class, 'index'])->name('studio-operations.index');
    Route::put('studio-operations/hours', [StudioOperationController::class, 'updateHours'])->name('studio-operations.hours');
    Route::post('studio-operations/blocks', [StudioOperationController::class, 'block'])->name('studio-operations.block');
    Route::patch('studio-operations/blocks/{studioSchedule}', [StudioOperationController::class, 'unblock'])->name('studio-operations.unblock');
    Route::get('booking-availability/{studio}', [BookingController::class, 'availability'])->name('bookings.availability');
    Route::resource('bookings', BookingController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    Route::patch('bookings/{booking}/decision', [BookingController::class, 'decide'])->name('bookings.decide');
    Route::get('bookings/{booking}/payment-proof', [BookingController::class, 'paymentProof'])->name('bookings.payment-proof');
    Route::resource('users', UserController::class)->except('show');
    Route::resource('studios', StudioController::class)->except('show');
    Route::resource('dance-classes', DanceClassController::class)
        ->parameters(['dance-classes' => 'danceClass'])
        ->except('show');
});
