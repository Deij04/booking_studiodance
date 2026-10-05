<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Studio;
use App\Services\BookingAvailability;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudioController extends Controller
{
    public function index(Request $request, BookingAvailability $availability): View
    {
        $filters = $request->validate([
            'date' => ['nullable', 'required_with:start_time,end_time', 'date_format:Y-m-d', 'after_or_equal:today', 'before:2100-01-01'],
            'start_time' => ['nullable', 'required_with:end_time', 'date_format:H:i'],
            'end_time' => ['nullable', 'required_with:start_time', 'date_format:H:i', 'after:start_time'],
            'studio_id' => ['nullable', 'integer'],
        ]);
        $allStudios = Studio::query()->where('status', 'active')->orderBy('name')->orderBy('id')->get();
        $studios = $allStudios->filter(fn ($studio) => empty($filters['studio_id']) || $studio->id == $filters['studio_id']);
        $checkedAvailability = !empty($filters['date']);
        if ($checkedAvailability) {
            $studios = $studios->filter(function ($studio) use ($availability, $filters) {
                $slots = $availability->month($studio, substr($filters['date'], 0, 7))[$filters['date']] ?? [];
                if (empty($filters['start_time'])) return $slots !== [];
                return in_array($filters['end_time'], $slots[$filters['start_time']] ?? [], true);
            });
        }

        return view('user.studios.index', [
            'studios' => $studios, 'allStudios' => $allStudios,
            'filters' => $filters, 'checkedAvailability' => $checkedAvailability,
        ]);
    }
}
