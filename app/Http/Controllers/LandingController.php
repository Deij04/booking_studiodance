<?php

namespace App\Http\Controllers;

use App\Models\Studio;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function index(): View
    {
        return view('landing.index', [
            'studios' => Studio::query()->where('status', 'active')->orderBy('name')->orderBy('id')->get(),
        ]);
    }
}
