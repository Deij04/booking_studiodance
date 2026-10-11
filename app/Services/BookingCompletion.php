<?php
namespace App\Services;
use App\Models\ClassRegistration;
use App\Models\Booking;
class BookingCompletion
{
    public function run(?int $id = null): int
    {
        $count = 0;
        $groups = [[Booking::class, 'status', null]];
        if ($id === null) $groups[] = [ClassRegistration::class, 'registration_status', 'classSchedule'];
        foreach ($groups as [$model, $status, $schedule]) {
            $records = $model::when($id !== null, fn ($q) => $q->whereKey($id))->where($status, 'confirmed')
                ->whereHas('payment', fn ($p) => $p->where('verification_status', 'verified'))
                ->when($schedule, fn ($q) => $q->whereHas($schedule, $elapsed = fn ($q) => $q->where('date', '<', today()->toDateString())->orWhere(fn ($q) => $q->whereDate('date', today())->whereTime('end_time', '<=', now()->format('H:i:s')))), fn ($q) => $q->where(fn ($q) => $q->where('date', '<', today()->toDateString())->orWhere(fn ($q) => $q->whereDate('date', today())->whereTime('end_time', '<=', now()->format('H:i:s')))))->get();
            foreach ($records as $record) {
                if (app(PaymentSummary::class)->for($record)['remaining'] <= 0) {
                    $count += $model::whereKey($record->id)->where($status, 'confirmed')->update([$status => 'completed']);
                }
            }
        }
        return $count;
    }
}
