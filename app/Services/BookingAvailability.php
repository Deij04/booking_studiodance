<?php

namespace App\Services;

use App\Models\Studio;
use Carbon\Carbon;

class BookingAvailability
{
    public function month(Studio $studio, string $month, ?int $excludeScheduleId = null): array
    {
        $first = Carbon::createFromFormat('!Y-m', $month)->startOfMonth();
        $last = $first->copy()->endOfMonth();
        $occupied = $studio->bookings()->when($excludeScheduleId, fn ($q) => $q->where('id', '!=', $excludeScheduleId))
            ->whereBetween('date', [$first->toDateString(), $last->toDateString()])->where('status', '!=', 'cancelled')->get(['date', 'start_time', 'end_time']);
        $blocks = $studio->bookingSettings()->blocked()->whereBetween('date', [$first->toDateString(), $last->toDateString()])->get(['date', 'start_time', 'end_time']);
        $classes = $studio->classSchedules()->where('status', 'active')
            ->whereBetween('date', [$first->toDateString(), $last->toDateString()])->get(['date', 'start_time', 'end_time']);
        $busy = $occupied->toBase()->merge($blocks)->merge($classes)->groupBy('date');
        $days = [];
        $now = now();
        $hours = app(BookingHours::class)->current();
        for ($day = $first->copy(); $day->lte($last); $day->addDay()) {
            $date = $day->toDateString();
            $slots = [];
            $opening = Carbon::parse($date.' '.$hours['opens_at']);
            $closing = Carbon::parse($date.' '.$hours['closes_at']);
            $intervals = collect($busy[$date] ?? [])->map(fn ($slot) => [Carbon::parse($date.' '.$slot->start_time), Carbon::parse($date.' '.$slot->end_time)]);
            for ($start = $opening->copy(); $start->lt($closing); $start->addMinutes(config('booking.step_minutes'))) {
                if ($start->lte($now)) {
                    continue;
                }
                $ends = [];
                for ($end = $start->copy()->addMinutes(config('booking.minimum_minutes')); $end->lte($closing); $end->addMinutes(config('booking.step_minutes'))) {
                    if ($intervals->contains(fn ($range) => $start->lt($range[1]) && $end->gt($range[0]))) {
                        break;
                    }
                    $ends[] = $end->format('H:i');
                }
                if ($ends !== []) {
                    $slots[$start->format('H:i')] = $ends;
                }
            }
            $days[$date] = $slots;
        }

        return $days;
    }
}
