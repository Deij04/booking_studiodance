<?php

namespace App\Http\Controllers;

use App\Models\DanceClass;
use App\Models\Studio;
use App\Services\BookingAvailability;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function index(): View
    {
        $studios = Studio::where('status', 'active')->orderBy('name')->orderBy('id')->get();
        $date = today()->toDateString();
        $availability = collect();
        foreach ($studios as $studio) {
            $slots = app(BookingAvailability::class)->month($studio, substr($date, 0, 7))[$date] ?? [];
            foreach (array_slice($slots, 0, 2, true) as $start => $ends) {
                $availability->push(['studio' => $studio, 'date' => $date, 'start' => $start, 'end' => $ends[0]]);
            }
        }
        $availability = $availability->sortBy('start')->take(4)->values();
        $danceClasses = DanceClass::where('status', 'active')->orderBy('name')->limit(6)->get();

        return view('landing.index', compact('studios', 'availability', 'danceClasses'));
    }
}
