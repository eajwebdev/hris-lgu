<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * The shared-secret gate for the event API (list / login / logs).
 *
 * The mobile event-scanner app carries a static passcode and passes it as the
 * first route segment. Previously each controller action compared it inline
 * with a loose `==` against a literal baked into the source. This centralises
 * that check: the secret lives in config (config/api.php, env-overridable) and
 * is compared in constant time, so the endpoints cannot be probed for the
 * passcode a byte at a time, and the controllers no longer carry the secret.
 *
 * On failure it returns a flat 401 JSON and the request never reaches the
 * controller — the same boundary an authenticated route gets, for endpoints
 * that are otherwise public.
 */
class VerifyEventApiPasscode
{
    public function handle(Request $request, Closure $next)
    {
        $expected = (string) config('api.event_passcode');
        $provided = (string) $request->route('passcode');

        if ($expected === '' || ! hash_equals($expected, $provided)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
