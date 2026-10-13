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
Route::get('/classes', [\App\Http\Controllers\User\ClassController::class, 'index'])->name('user.classes.index');
Route::get('/studios', [\App\Http\Controllers\User\StudioController::class, 'index'])->name('user.studios.index');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
    Route::get('/register', [AuthController::class, 'createRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
    Route::get('/forgot-password', [\App\Http\Controllers\PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [\App\Http\Controllers\PasswordResetController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [\App\Http\Controllers\PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [\App\Http\Controllers\PasswordResetController::class, 'update'])->middleware('throttle:5,1')->name('password.update');
});

Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');
Route::middleware('auth')->group(function () {
    Route::get('/history/{type}/{id}', [\App\Http\Controllers\User\HistoryReservationController::class, 'show'])->where('type', 'studio|class')->whereNumber('id')->name('user.history.detail');
    Route::post('/history/{type}/{id}/settlement', [\App\Http\Controllers\User\HistoryReservationController::class, 'settle'])->where('type', 'studio|class')->whereNumber('id')->name('user.history.settle');
    Route::get('/classes/{danceClass}/reserve', [\App\Http\Controllers\User\ClassReservationController::class, 'create'])->name('user.classes.reserve');
    Route::post('/classes/{danceClass}/reserve', [\App\Http\Controllers\User\ClassReservationController::class, 'store'])->name('user.classes.store');
    Route::get('/bookings/create', [\App\Http\Controllers\User\BookingController::class, 'create'])->name('user.bookings.create');
    Route::post('/bookings', [\App\Http\Controllers\User\BookingController::class, 'store'])->name('user.bookings.store');
    Route::get('/bookings/availability/{studio}', [\App\Http\Controllers\User\BookingController::class, 'availability'])->name('user.bookings.availability');
    Route::get('/profile', [\App\Http\Controllers\User\AccountController::class, 'profile'])->name('user.profile');
    Route::patch('/profile', [\App\Http\Controllers\User\AccountController::class, 'update'])->name('user.profile.update');
    Route::get('/history', [\App\Http\Controllers\User\AccountController::class, 'history'])->name('user.history');
    Route::get('/memberships', [CustomerMembershipController::class, 'index'])->name('memberships.index');
    Route::post('/memberships', [CustomerMembershipController::class, 'store'])->name('memberships.store');
});

Route::get('/dashboard', function (Request $request) {
    return redirect()->route($request->user()->can('access-admin') ? 'admin.dashboard' : 'landing');
})
    ->middleware('auth')
    ->name('dashboard');

Route::middleware(['auth', 'can:access-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::patch('/settlements/{settlement}', [\App\Http\Controllers\Admin\SettlementController::class, 'decide'])->name('settlements.decide');
    Route::get('/settlements/{settlement}/proof', [\App\Http\Controllers\Admin\SettlementController::class, 'proof'])->name('settlements.proof');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('memberships/{membership}/payment-proof', [MembershipController::class, 'paymentProof'])->name('memberships.payment-proof');
    Route::post('memberships/{membership}/transfer', [MembershipController::class, 'transfer'])->name('memberships.transfer');
    Route::resource('memberships', MembershipController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    Route::patch('memberships/{membership}/decision', [MembershipController::class, 'decide'])->name('memberships.decide');
    Route::post('membership-plans/{membershipPlan}/transfer', [MembershipPlanController::class, 'transfer'])->name('membership-plans.transfer');
    Route::resource('membership-plans', MembershipPlanController::class)->only(['create', 'store', 'edit', 'update', 'destroy']);
    Route::get('reports/download', [\App\Http\Controllers\Admin\ReportController::class, 'download'])->name('reports.download');
    Route::get('class-registrations/{classRegistration}/payment-proof', [ClassRegistrationController::class, 'paymentProof'])->name('class-registrations.payment-proof');
    Route::get('class-registrations/{classRegistration}/verify', [ClassRegistrationController::class, 'verification'])->name('class-registrations.verify');
    Route::patch('class-registrations/{classRegistration}/decision', [ClassRegistrationController::class, 'decide'])->name('class-registrations.decide');
    Route::get('class-registrations/{classRegistration}/settlement', [ClassRegistrationController::class, 'settlementForm'])->name('class-registrations.settlement-form');
    Route::post('class-registrations/{classRegistration}/settlement', [ClassRegistrationController::class, 'settle'])->name('class-registrations.settle');
    Route::resource('class-registrations', ClassRegistrationController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::resource('class-schedules', ClassScheduleController::class)->only(['create', 'store', 'edit', 'update']);
    Route::patch('class-schedules/{classSchedule}/deactivate', [ClassScheduleController::class, 'deactivate'])->name('class-schedules.deactivate');
    Route::get('studio-operations', [StudioOperationController::class, 'index'])->name('studio-operations.index');
    Route::put('studio-operations/hours', [StudioOperationController::class, 'updateHours'])->name('studio-operations.hours');
    Route::post('studio-operations/blocks', [StudioOperationController::class, 'block'])->name('studio-operations.block');
    Route::patch('studio-operations/blocks/{bookingSetting}', [StudioOperationController::class, 'unblock'])->name('studio-operations.unblock');
    Route::get('booking-availability/{studio}', [BookingController::class, 'availability'])->name('bookings.availability');
    Route::resource('bookings', BookingController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::get('bookings/{booking}/settlement', [BookingController::class, 'settlementForm'])->name('bookings.settlement-form');
    Route::patch('bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::post('bookings/{booking}/settlement', [BookingController::class, 'settle'])->name('bookings.settle');
    Route::patch('bookings/{booking}/decision', [BookingController::class, 'decide'])->name('bookings.decide');
    Route::get('bookings/{booking}/payment-proof', [BookingController::class, 'paymentProof'])->name('bookings.payment-proof');
    Route::resource('users', UserController::class)->except('show');
    Route::resource('studios', StudioController::class)->except('show');
    Route::resource('dance-classes', DanceClassController::class)
        ->parameters(['dance-classes' => 'danceClass'])
        ->except('show');
});
