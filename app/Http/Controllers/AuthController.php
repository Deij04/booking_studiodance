<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt([...$credentials, 'status' => 'active'], $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->route($request->user()->can('access-admin') ? 'admin.dashboard' : 'landing');
        }

        return back()
            ->withErrors(['email' => 'Email atau password salah, atau akun tidak aktif.'])
            ->onlyInput('email');
    }

    public function createRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'address' => ['required', 'string', 'max:500'],
            'state' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:25'],
            'terms' => ['accepted'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'address' => $validated['address'],
            'state' => $validated['state'],
            'phone' => '+62'.$validated['phone'],
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('landing');
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
