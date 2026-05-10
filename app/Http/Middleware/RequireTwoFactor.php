<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactor
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // If not logged in at all, let the auth middleware handle it
        if (!$user) {
            return $next($request);
        }

        $routeName = $request->route() ? $request->route()->getName() : null;

        // These routes are always allowed
        $allowedRoutes = [
            '2fa.setup',
            '2fa.confirm',
            '2fa.verify',
            '2fa.authenticate',
            'logout',
            'login',
            'register',
        ];

        if (in_array($routeName, $allowedRoutes)) {
            return $next($request);
        }

        // User is logged in but 2FA not set up yet — force setup
        if (!$user->google2fa_enabled) {
            return redirect()->route('2fa.setup');
        }

        // User is logged in, 2FA enabled, but not yet verified this session
        if (!$request->session()->get('2fa_verified')) {
            return redirect()->route('2fa.verify');
        }

        return $next($request);
    }
}
