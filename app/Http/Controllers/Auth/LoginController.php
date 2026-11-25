<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'role' => ['required', 'in:admin,dosen,mahasiswa'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($credentials['email']).'|'.$request->ip());
        $lockoutKey = $throttleKey.':lockout';

        if (RateLimiter::tooManyAttempts($lockoutKey, 1)) {
            return back()->withErrors([
                'email' => 'Terlalu banyak percobaan login. Coba lagi dalam '.RateLimiter::availableIn($lockoutKey).' detik.',
            ])->withInput([
                'role' => $credentials['role'],
            ]);
        }

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ])) {
            RateLimiter::hit($throttleKey, 60);

            if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
                RateLimiter::hit($lockoutKey, 60);

                return back()->withErrors([
                    'email' => 'Terlalu banyak percobaan login. Coba lagi dalam '.RateLimiter::availableIn($lockoutKey).' detik.',
                ])->withInput([
                    'role' => $credentials['role'],
                ]);
            }

            return back()->withErrors([
                'email' => 'Email atau password salah.',
            ])->withInput([
                'role' => $credentials['role'],
            ]);
        }

        RateLimiter::clear($throttleKey);
        RateLimiter::clear($lockoutKey);

        if ($user->role !== $credentials['role']) {
            Auth::logout();

            return back()->withErrors([
                'role' => 'Role yang dipilih tidak cocok dengan akun ini.',
            ])->withInput();
        }

        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
