<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Make every `/api/*` request a JSON request.
 *
 * Without this, a client that omits `Accept: application/json` gets the HTML
 * error page for a 401/403/404/419/500 — a login redirect or a stack-trace-ish
 * page instead of a parseable body. Forcing the Accept header means the
 * framework's exception handler always renders JSON on these routes, which is
 * what an API client expects and what keeps error details from leaking through
 * a debug HTML page.
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next)
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
