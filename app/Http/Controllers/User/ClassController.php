<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\DanceClass;
use App\Models\Studio;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'class_id' => ['nullable', 'integer'],
            'studio_id' => ['nullable', 'integer'],
            'date' => ['nullable', 'required_with:start_time,end_time', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time' => ['nullable', 'required_with:end_time', 'date_format:H:i'],
            'end_time' => ['nullable', 'required_with:start_time', 'date_format:H:i', 'after:start_time'],
        ]);
        $studioOptions = Studio::where('status', 'active')->orderBy('name')->get(['id', 'name']);
        $classOptions = DanceClass::where('status', 'active')->orderBy('name')->get(['id', 'name']);
        $classes = DanceClass::where('status', 'active')
            ->when(!empty($filters['class_id']), fn ($query) => $query->where('id', $filters['class_id']))
            ->with(['classSchedules' => fn ($query) => $query->where('status', 'active')
                ->when($filters['studio_id'] ?? null, fn ($query, $id) => $query->where('studio_id', $id))
                ->when($filters['date'] ?? null, fn ($query, $date) => $query->whereDate('date', $date))
                ->when($filters['start_time'] ?? null, fn ($query, $time) => $query->whereTime('start_time', '>=', $time))
                ->when($filters['end_time'] ?? null, fn ($query, $time) => $query->whereTime('end_time', '<=', $time))
                ->whereHas('studio', fn ($studio) => $studio->where('status', 'active'))
                ->where(fn ($date) => $date->where('date', '>', today()->toDateString())
                    ->orWhere(fn ($today) => $today->where('date', today()->toDateString())->where('start_time', '>', now()->format('H:i:s'))))
                ->with('studio')->withCount(['classRegistrations as occupied' => fn ($registration) => $registration->where('registration_status', '!=', 'cancelled')])
                ->orderBy('date')->orderBy('start_time')])
            ->orderBy('name')->get();

        if (!empty($filters['studio_id']) || !empty($filters['date'])) {
            $classes = $classes->filter(fn ($class) => $class->classSchedules->contains(fn ($schedule) => $schedule->occupied < $schedule->capacity));
        }

        return view('user.classes.index', compact('classes', 'classOptions', 'filters', 'studioOptions'));
    }
}
