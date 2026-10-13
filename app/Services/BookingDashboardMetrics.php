<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\ClassRegistration;
use App\Models\Payment;
use Carbon\Carbon;

class BookingDashboardMetrics
{
    public function get(array $filters = [], bool $forClasses = false): array
    {
        $period = $filters['period'] ?? 'week';
        if ($period === 'month') {
            $filters = ['year' => $filters['year'] ?? now()->year];
        }
        $revenue = (float) Payment::whereNotNull($forClasses ? 'class_registration_id' : 'booking_id')->where('verification_status', 'verified')->sum('amount');
        $year = (int) ($filters['year'] ?? now()->year);
        $month = (int) ($filters['month'] ?? (array_key_exists('year', $filters) || !empty($filters['from']) ? 0 : now()->month));
        $base = Carbon::create($year, $month ?: 1, 1)->startOfDay();
        $from = !empty($filters['from']) ? Carbon::parse($filters['from'])->startOfDay() : $base->copy();
        $to = !empty($filters['to']) ? Carbon::parse($filters['to'])->endOfDay() : ($month ? $base->copy()->endOfMonth() : $base->copy()->endOfYear());
        if ($forClasses) {
            $dates = ClassRegistration::whereIn('registration_status', ['confirmed', 'completed'])
                ->whereHas('payment', fn ($p) => $p->where('verification_status', 'verified'))
                ->whereHas('classSchedule', fn ($q) => $q->whereBetween('date', [$from->toDateString(), $to->toDateString()]))
                ->with('classSchedule')->get()->map(fn ($record) => Carbon::parse($record->classSchedule->date));
        } else {
            $dates = Booking::whereIn('status', ['confirmed', 'completed'])->whereHas('payment', fn ($p) => $p->where('verification_status', 'verified'))
                ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                ->get()->map(fn ($record) => Carbon::parse($record->date));
        }
        $charts = [];
        foreach (['week' => 'Mingguan', 'month' => 'Bulanan'] as $unit => $label) {
            $bucket = fn ($date) => match ($unit) {
                'week' => $date->copy()->startOfWeek()->format('Y-m-d'),
                'month' => $date->format('Y-m'),
                'year' => $date->format('Y'),
            };
            $grouped = $dates->countBy($bucket);
            $first = match ($unit) { 'week' => $from->copy()->startOfWeek(), 'month' => $from->copy()->startOfMonth(), 'year' => $from->copy()->startOfYear() };
            $points = [];
            for ($date = $first; $date->lte($to); $date->addUnit($unit)) {
                $caption = match ($unit) {
                    'week' => $date->copy()->max($from)->format('d/m/Y').' - '.$date->copy()->endOfWeek()->min($to)->format('d/m/Y'),
                    'month' => $date->copy()->locale('id')->translatedFormat('F Y'),
                    'year' => $date->format('Y'),
                };
                $points[] = ['label' => $caption, 'count' => $grouped[$bucket($date)] ?? 0];
            }
            $charts[$unit] = ['label' => $label, 'points' => $points, 'total' => $dates->count()];
        }
        return ['revenue' => round($revenue, 2), 'charts' => $charts,
            'filters' => ['period' => $period, 'from' => $from->toDateString(), 'to' => $to->toDateString(), 'month' => $month, 'year' => $year]];
    }
}
