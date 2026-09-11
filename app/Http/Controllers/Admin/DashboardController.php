<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\Reservation;
use App\Models\StudioSchedule;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $upcomingClasses = ClassSchedule::query()
            ->with(['danceClass', 'studio'])
            ->where('status', 'active')
            ->whereDate('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit(4)
            ->get();

        $bookingCounts = Reservation::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.dashboard.index', [
            'user' => $user,
            'reservationCount' => $bookingCounts->sum(),
            'pendingCount' => $bookingCounts['pending'] ?? 0,
            'confirmedCount' => $bookingCounts['confirmed'] ?? 0,
            'historyCount' => ($bookingCounts['completed'] ?? 0) + ($bookingCounts['cancelled'] ?? 0),
            'pendingReservations' => Reservation::with(['user', 'studioSchedule.studio'])
                ->where('status', 'pending')->oldest('id')->limit(5)->get(),
            'upcomingReservations' => Reservation::with(['user', 'studioSchedule.studio'])
                ->where('status', 'confirmed')
                ->whereHas('studioSchedule', fn ($query) => $query
                    ->where('date', '>', today()->toDateString())
                    ->orWhere(fn ($query) => $query->whereDate('date', today())->whereTime('end_time', '>', now()->format('H:i:s'))))
                ->orderBy(StudioSchedule::select('date')->whereColumn('studio_schedules.id', 'reservations.studio_schedule_id'))
                ->orderBy(StudioSchedule::select('start_time')->whereColumn('studio_schedules.id', 'reservations.studio_schedule_id'))
                ->orderBy('id')->limit(5)->get(),
            'upcomingClasses' => $upcomingClasses,
        ]);
    }
}
