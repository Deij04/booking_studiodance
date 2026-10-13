<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function request()
    {
        return view('auth.password', ['reset' => false]);
    }

    public function email(Request $request)
    {
        $request->validate(['email' => ['required', 'email', 'max:255']]);
        $status = Password::sendResetLink($request->only('email'));

        if (! in_array($status, [Password::ResetLinkSent, Password::InvalidUser], true)) {
            return back()->withErrors(['email' => __($status)])->onlyInput('email');
        }

        return back()->with('status', 'Jika email terdaftar, tautan reset password telah dikirim. Periksa kotak masuk dan folder spam.');
    }

    public function edit(Request $request, string $token)
    {
        return view('auth.password', ['reset' => true, 'token' => $token, 'email' => $request->query('email', '')]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });

        return $status === Password::PasswordReset
            ? to_route('login')->with('status', 'Password berhasil direset. Silakan login dengan password baru.')
            : back()->withErrors(['email' => __($status)])->onlyInput('email');
    }
}
