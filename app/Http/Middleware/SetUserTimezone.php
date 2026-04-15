<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * SetUserTimezone
 *
 * Dynamically applies the authenticated user's preferred timezone
 * to both the PHP runtime and Laravel's Carbon for the entire request.
 * This means any timestamp generated server-side (logs, Carbon::now(),
 * scheduled tasks) will be expressed in the user's timezone.
 */
class SetUserTimezone
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $timezone = Auth::user()->timezone ?? config('app.timezone');

            // Validate it's a real IANA timezone string before applying
            if (in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
                config(['app.timezone' => $timezone]);
                date_default_timezone_set($timezone);
            }
        }

        return $next($request);
    }
}
