<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Holds an account on the change-password screen while it is still using the
 * password HR issued it.
 *
 * The issued password is the same for everybody and is handed out over the
 * counter, so an account still carrying it is not really protected by it. This
 * lets such an account sign in — refusing would leave the employee with no way
 * to fix it — but nowhere else until a new password is set.
 *
 * The verdict is decided once at sign-in (see LoginAuthController) and carried
 * in the session, because answering it here would mean a bcrypt comparison on
 * every single request, which costs tens of milliseconds a time.
 */
class EnsurePasswordChanged
{
    public const SESSION_KEY = 'must_change_password';

    /**
     * Routes an account in this state may still reach: the screen it is being
     * sent to, the form post that resolves it, and the way out. Without the
     * last one somebody who does not want to change their password would have
     * no way to even log out.
     */
    private const ALLOWED = [
        'password.change',
        'password.change.update',
        'logout',
    ];

    public function handle(Request $request, Closure $next)
    {
        if (! $request->session()->get(self::SESSION_KEY)) {
            return $next($request);
        }

        if (in_array($request->route()?->getName(), self::ALLOWED, true)) {
            return $next($request);
        }

        // An AJAX caller cannot follow a redirect into an HTML page, and the
        // dashboard fires several on load. Answer in kind so those fail
        // visibly instead of quietly parsing a login page as JSON.
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status'  => 423,
                'message' => 'Please set a new password before continuing.',
            ], 423);
        }

        return redirect()->route('password.change');
    }

    /**
     * Whether this account is still on the issued password.
     *
     * Called once per sign-in. A blank hash (an account HR has not finished
     * setting up) counts as "must change" rather than "fine": there is no
     * password to keep.
     */
    public static function isDefault(?string $hashedPassword): bool
    {
        if (blank($hashedPassword)) {
            return true;
        }

        $default = (string) config('auth.default_password');

        if ($default === '') {
            return false;
        }

        return Hash::check($default, $hashedPassword);
    }
}
