<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\Booking;

class DashboardController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        if ($request->input('period') === 'month') {
            $request->merge(['from' => null, 'to' => null, 'month' => null]);
        }
        $filters = $request->validate([
            'period' => ['nullable', \Illuminate\Validation\Rule::in(['week', 'month'])],
            'from' => ['nullable', 'required_with:to', 'date_format:Y-m-d'],
            'to' => ['nullable', 'required_with:from', 'date_format:Y-m-d', 'after_or_equal:from'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:1900,2100'],
        ]);
        if (!empty($filters['from']) && \Carbon\Carbon::parse($filters['from'])->diffInDays($filters['to']) > 3660) {
            throw \Illuminate\Validation\ValidationException::withMessages(['to' => 'Rentang grafik maksimal 10 tahun.']);
        }
        $user = auth()->user();

        $upcomingClasses = ClassSchedule::query()
            ->with(['danceClass', 'studio'])
            ->where('status', 'active')
            ->whereDate('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit(4)
            ->get();

        $bookingCounts = Booking::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $classCounts = \App\Models\ClassRegistration::query()->selectRaw('registration_status, COUNT(*) as total')->groupBy('registration_status')->pluck('total', 'registration_status');
        $membershipRevenue = $user->role === 'super_admin' ? (float) \App\Models\Membership::where('payment_status', 'paid')->sum('price') : 0;

        return view('admin.dashboard.index', [
            'user' => $user,
            'membershipRevenue' => $membershipRevenue,
            'classReservationCount' => $classCounts->sum(),
            'classPendingCount' => \App\Models\ClassRegistration::inReservationTab('requests')->count(),
            'classActiveCount' => \App\Models\ClassRegistration::inReservationTab('active')->count(),
            'classHistoryCount' => ($classCounts['completed'] ?? 0) + ($classCounts['cancelled'] ?? 0),
            'bookingMetrics' => app(\App\Services\BookingDashboardMetrics::class)->get($filters),
            'classMetrics' => app(\App\Services\BookingDashboardMetrics::class)->get($filters, true),
            'reservationCount' => $bookingCounts->sum(),
            'pendingCount' => Booking::inBookingTab('requests')->count(),
            'confirmedCount' => Booking::inBookingTab('active')->count(),
            'historyCount' => ($bookingCounts['completed'] ?? 0) + ($bookingCounts['cancelled'] ?? 0),
            'pendingReservations' => Booking::with(['user', 'studio'])
                ->inBookingTab('requests')->oldest('id')->limit(5)->get(),
            'upcomingReservations' => Booking::with(['user', 'studio'])
                ->inBookingTab('active')
                ->where(fn ($query) => $query
                    ->where('date', '>', today()->toDateString())
                    ->orWhere(fn ($query) => $query->whereDate('date', today())->whereTime('end_time', '>', now()->format('H:i:s'))))
                ->orderBy('date')->orderBy('start_time')
                ->orderBy('id')->limit(5)->get(),
            'upcomingClasses' => $upcomingClasses,
        ]);
    }
}
