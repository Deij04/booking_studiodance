<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function profile(Request $request): View
    {
        return view('user.account.profile', ['user' => $request->user()]);
    }

    public function history(Request $request): View
    {
        $user = $request->user();

        return view('user.account.history', [
            'bookings' => $user->bookings()->with(['studio', 'payment', 'settlement'])->latest('id')->paginate(10, ['*'], 'bookings_page')->withQueryString(),
            'registrations' => $user->classRegistrations()->with(['classSchedule.danceClass', 'payment', 'settlement'])->latest('id')->paginate(10, ['*'], 'classes_page')->withQueryString(),
            'memberships' => $user->memberships()->with('membershipPlan')->latest('id')->paginate(10, ['*'], 'memberships_page')->withQueryString(),
        ]);
    }
}
