<?php

namespace App\Services;

use App\Models\Studio;

class StudioConflicts
{
    // Call while holding the studio row lock when creating/updating a schedule.
    public function exists(Studio $studio, array $data, ?int $exceptClass = null, ?int $exceptBooking = null): bool
    {
        $overlap = fn ($query) => $query->whereDate('date', $data['date'])
            ->whereTime('start_time', '<', substr($data['end_time'], 0, 5).':00')
            ->whereTime('end_time', '>', substr($data['start_time'], 0, 5).':00');

        return $studio->classSchedules()->where('status', 'active')
            ->when($exceptClass, fn ($q) => $q->where('id', '!=', $exceptClass))->where($overlap)->exists()
            || $studio->bookings()->where('status', '!=', 'cancelled')->when($exceptBooking, fn ($q) => $q->whereKeyNot($exceptBooking))->where($overlap)->exists()
            || $studio->bookingSettings()->blocked()->where($overlap)->exists();
    }
}
