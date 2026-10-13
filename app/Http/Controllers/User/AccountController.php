<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function profile(Request $request): View
    {
        $user = $request->user();
        $memberships = $user->memberships()->with('membershipPlan')->validOn(today()->toDateString())->latest('id')->get();

        return view('user.account.profile', compact('user', 'memberships'));
    }

    public function history(Request $request): View
    {
        $user = $request->user();

        return view('user.account.history', [
            'bookings' => $user->bookings()->with(['studio', 'payment', 'settlement'])->latest('id')->paginate(10, ['*'], 'bookings_page')->withQueryString(),
            'registrations' => $user->classRegistrations()->with(['classSchedule.danceClass', 'payment', 'settlement'])->latest('id')->paginate(10, ['*'], 'classes_page')->withQueryString(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:25'],
            'address' => ['nullable', 'string', 'max:500'],
            'state' => ['nullable', 'string', 'max:100'],
        ]);

        $user->update($validated);

        return to_route('user.profile')->with('status', 'Profil berhasil diperbarui.');
    }
}
