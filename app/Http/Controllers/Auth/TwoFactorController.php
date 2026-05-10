<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    /**
     * Show 2FA setup page (after registration).
     */
    public function setup(Request $request)
    {
        $user = $request->user();

        if ($user->google2fa_enabled) {
            return redirect()->route('dashboard');
        }

        $google2fa = new Google2FA();

        if (!$user->google2fa_secret) {
            $user->google2fa_secret = $google2fa->generateSecretKey();
            $user->save();
        }

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $user->google2fa_secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(250),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $svg = $writer->writeString($qrCodeUrl);

        return Inertia::render('auth/TwoFactorSetup', [
            'qrCodeSvg' => $svg,
            'secret' => $user->google2fa_secret,
        ]);
    }

    /**
     * Confirm 2FA setup code and enable 2FA (after registration).
     */
    public function confirmSetup(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = $request->user();
        $google2fa = new Google2FA();

        $valid = $google2fa->verifyKey($user->google2fa_secret, $request->code);

        if ($valid) {
            $user->google2fa_enabled = true;
            $user->save();
            $request->session()->put('2fa_verified', true);
            return redirect()->route('dashboard');
        }

        return back()->withErrors(['code' => 'The provided authentication code is invalid. Please try again.']);
    }

    /**
     * Show the 2FA verification page (Step 2 of login).
     * Uses email stored in session from Step 1.
     */
    public function verify(Request $request)
    {
        // If already fully logged in and 2FA verified, skip
        if ($request->user() && $request->session()->get('2fa_verified')) {
            return redirect()->route('dashboard');
        }

        // Get email from session (set during Step 1)
        $email = $request->session()->get('2fa_login_email');

        if (!$email) {
            return redirect()->route('login');
        }

        return Inertia::render('auth/TwoFactorVerify', [
            'email' => $email,
        ]);
    }

    /**
     * Authenticate using email from session + Google Authenticator code.
     */
    public function authenticate(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $email = $request->session()->get('2fa_login_email');
        $remember = $request->session()->get('2fa_login_remember', false);

        if (!$email) {
            return redirect()->route('login');
        }

        $throttleKey = '2fa|' . Str::lower($email) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'code' => "Too many attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        $user = User::where('email', $email)->first();

        if (!$user || !$user->google2fa_secret) {
            return redirect()->route('login');
        }

        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey($user->google2fa_secret, $request->code);

        if (!$valid) {
            RateLimiter::hit($throttleKey);
            throw ValidationException::withMessages([
                'code' => 'The provided authentication code is invalid. Please try again.',
            ]);
        }

        // Code is valid — log the user in
        RateLimiter::clear($throttleKey);
        Auth::login($user, $remember);
        $request->session()->regenerate();
        $request->session()->forget('2fa_login_email');
        $request->session()->forget('2fa_login_remember');
        $request->session()->put('2fa_verified', true);

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
