<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Only Admin and HR may touch face data.
 *
 * This is the enforcement point, not the Blade `@if` that hides the button.
 * Typing the URL by hand, replaying the POST, or reaching the route from the
 * employee guard all land here and get a 403 — hiding a control is presentation,
 * refusing the request is the security boundary.
 */
class EnsureFaceRegistrar
{
    public const ROLES = ['Administrator', 'HR Administrator'];

    public function handle(Request $request, Closure $next)
    {
        if (! self::allows()) {
            abort(403, 'You are not authorised to manage face recognition data.');
        }

        return $next($request);
    }

    /**
     * The same question the middleware asks, for views that decide whether to
     * draw the controls at all. One definition, so the button and the route it
     * posts to can never disagree about who is allowed.
     */
    public static function allows(): bool
    {
        $user = auth()->guard('web')->user();

        return $user !== null && in_array($user->role, self::ROLES, true);
    }
}
