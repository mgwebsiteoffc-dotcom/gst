<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ]);

        $key = Str::transliterate(Str::lower($data['email']).'|'.$request->ip());

        // Throttle brute-force attempts: 5 tries, then a lockout.
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many login attempts. Try again in '
                    .RateLimiter::availableIn($key).' seconds.',
            ]);
        }

        if (! Auth::attempt(
            ['email' => $data['email'], 'password' => $data['password']],
            $request->boolean('remember')
        )) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => 'Those credentials do not match our records.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        $user = Auth::user();

        // A disabled login is refused even with the correct password.
        if (! $user->is_active) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'This account has been disabled. Please contact support.',
            ]);
        }

        // A suspended business locks out its whole team.
        if ($user->tenant && $user->tenant->account_status !== 'active') {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Your business account is '.$user->tenant->account_status.'. Please contact support.',
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        // Partners who are not tenant users go to their own portal.
        if (! $user->tenant_id && ! $user->is_super_admin
            && \App\Models\Partner::where('user_id', $user->id)->exists()) {
            return redirect()->intended(route('partner.dashboard'));
        }

        if (! $user->is_super_admin && ! $user->tenant_id) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Your account is not linked to a business. Contact support.',
            ]);
        }

        return redirect()->intended(
            $user->is_super_admin ? route('admin.dashboard') : route('dashboard')
        );
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->forget('active_tenant_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', __('You have been signed out.'));
    }
}
