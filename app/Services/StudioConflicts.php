<?php

namespace App\Services;

use App\Models\Studio;

class StudioConflicts
{
    // Call while holding the studio row lock when creating/updating a schedule.
    public function exists(Studio $studio, array $data, ?int $exceptClass = null): bool
    {
        $overlap = fn ($query) => $query->whereDate('date', $data['date'])
            ->whereTime('start_time', '<', substr($data['end_time'], 0, 5).':00')
            ->whereTime('end_time', '>', substr($data['start_time'], 0, 5).':00');

        return $studio->classSchedules()->where('status', 'active')
            ->when($exceptClass, fn ($q) => $q->where('id', '!=', $exceptClass))->where($overlap)->exists()
            || $studio->studioSchedules()->where($overlap)
                ->where(fn ($q) => $q->whereIn('status', ['booked', 'blocked'])
                    ->orWhereHas('reservation', fn ($q) => $q->where('status', '!=', 'cancelled')))->exists();
    }
}
