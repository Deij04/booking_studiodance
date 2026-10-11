<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Admin\BookingController;
use App\Models\Studio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReservationController extends BookingController
{
    public function create(): View|RedirectResponse
    {
        abort_unless(auth()->user()->role === 'customer' && auth()->user()->status === 'active', 403);

        $studioId = old('studio_id', request('studio_id'));
        if (! $studioId) {
            return to_route('user.studios.index');
        }
        $studio = Studio::where('status', 'active')->findOrFail($studioId);

        return view('user.reservations.create', [
            'studio' => $studio,
            'hours' => app(\App\Services\BookingHours::class)->current(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->storeBooking($request, customer: true);
    }

    public function availability(Request $request, Studio $studio)
    {
        abort_unless($request->user()->role === 'customer' && $request->user()->status === 'active', 403);

        abort_unless($studio->status === 'active', 404);
        $data = $request->validate(['month' => ['required', 'date_format:Y-m']]);
        $first = \Carbon\Carbon::createFromFormat('!Y-m', $data['month']);
        $last = $first->copy()->endOfMonth();
        $occupied = $studio->studioSchedules()
            ->whereBetween('date', [$first->toDateString(), $last->toDateString()])
            ->where(fn ($query) => $query->whereIn('status', ['booked', 'blocked'])
                ->orWhereHas('reservation', fn ($booking) => $booking->where('status', '!=', 'cancelled')))
            ->get(['date', 'start_time', 'end_time', 'status'])
            ->map(fn ($slot) => ['date' => $slot->date, 'label' => $slot->status === 'blocked' ? 'Unavailable' : 'Booked',
                'start_time' => substr($slot->start_time, 0, 5), 'end_time' => substr($slot->end_time, 0, 5)]);
        $classes = $studio->classSchedules()->where('status', 'active')
            ->whereBetween('date', [$first->toDateString(), $last->toDateString()])
            ->get(['date', 'start_time', 'end_time'])
            ->map(fn ($slot) => ['date' => $slot->date, 'label' => 'Booked (Class)',
                'start_time' => substr($slot->start_time, 0, 5), 'end_time' => substr($slot->end_time, 0, 5)]);

        return response()->json([
            'days' => app(\App\Services\BookingAvailability::class)->month($studio, $data['month']),
            'occupied' => $occupied->concat($classes)->values(),
        ])->header('Cache-Control', 'private, no-store');
    }
}
