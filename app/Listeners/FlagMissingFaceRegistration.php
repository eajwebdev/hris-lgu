<?php

namespace App\Listeners;

use App\Models\Employee;
use Illuminate\Auth\Events\Login;

/**
 * Flag an employee who signs in with no face on file, so the dashboard can
 * prompt them once.
 *
 * WHY A LISTENER RATHER THAN A LINE IN THE CONTROLLER
 * There is more than one way into this application. The password form has an
 * afterLogin(), and GoogleAuthController has its own separate copy of one —
 * which is exactly how the first attempt at this missed Google sign-in
 * entirely: employees who use "Continue with Google" would never have been
 * prompted, and nothing would have reported it. Hanging this off the
 * framework's Login event means every path that authenticates somebody is
 * covered, including any added later, without either controller having to
 * remember.
 *
 * The prompt is a nudge, not a gate. An employee who never enrols simply cannot
 * use the attendance kiosk, and the point here is that they find that out on
 * the way in rather than while standing in front of it.
 */
class FlagMissingFaceRegistration
{
    /**
     * Set at sign-in, consumed by the dashboard on its first render.
     *
     * It lives in the session rather than being recomputed per request for two
     * reasons. "Is a face registered" is a query whose answer almost never
     * changes, and — more to the point — a prompt that reappears on every
     * navigation is one people learn to dismiss without reading. Once per
     * sign-in is a reminder; on every page it is noise.
     */
    public const SESSION_KEY = 'prompt_face_registration';

    public function handle(Login $event): void
    {
        // Employees only. Admin and HR enrol faces through the PDS, on whoever's
        // record they are working on, so prompting them about their own would be
        // prompting them about the wrong thing.
        if (! $event->user instanceof Employee) {
            return;
        }

        // No session to write to on a stateless guard (the device API tokens),
        // and nowhere to show a modal either.
        if (! request()->hasSession()) {
            return;
        }

        if (! $event->user->faceSummary()['registered']) {
            request()->session()->put(self::SESSION_KEY, true);
        }
    }
}
