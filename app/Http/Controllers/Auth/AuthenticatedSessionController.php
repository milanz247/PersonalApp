<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use PragmaRX\Google2FA\Google2FA;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page (Step 1 — Email).
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/Login', [
            'canResetPassword' => false,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle Step 1: Validate email, store in session, redirect to 2FA.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $throttleKey = Str::lower($request->email) . '|' . $request->ip();

        // Rate limiting
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            RateLimiter::hit($throttleKey);
            throw ValidationException::withMessages([
                'email' => 'No account found with that email address.',
            ]);
        }

        // Store email in session for Step 2
        $request->session()->put('2fa_login_email', $request->email);
        $request->session()->put('2fa_login_remember', $request->boolean('remember'));

        return redirect()->route('2fa.verify');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
