<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Zone recorded against a web-portal punch
    |--------------------------------------------------------------------------
    |
    | The DTR stores a device/zone id alongside every time entry. The retired
    | Android app derived one from GPS against the logzones table; the browser
    | portal has no equivalent, and no logzones are configured, so punches are
    | tagged with this id instead.
    |
    | Set FACE_PORTAL_ZONE_ID if you later want portal punches to land in a real
    | zone.
    |
    */

    'zone_id' => env('FACE_PORTAL_ZONE_ID', 0),

    /*
    |--------------------------------------------------------------------------
    | Cooldown
    |--------------------------------------------------------------------------
    |
    | Seconds that must pass before the same employee can record the same action
    | again. Stops a double-tap, a slow network retry, or somebody standing in
    | front of the camera a moment too long from writing two clock-ins.
    |
    */

    'cooldown_seconds' => 60,

    /*
    |--------------------------------------------------------------------------
    | Daily punch cap
    |--------------------------------------------------------------------------
    |
    | The most times, per day, an employee may clock in — and, separately, clock
    | out. The DTR holds these as comma-separated lists, and beyond a handful the
    | extra entries are always mistakes (a double-tap, someone re-scanning "just
    | to be sure"). Five each is generous for a normal day with a lunch break.
    |
    */

    'max_punches_per_day' => 5,

    /*
    |--------------------------------------------------------------------------
    | Geofencing
    |--------------------------------------------------------------------------
    |
    | Historically a punch outside every station's radius was tagged for HR,
    | never refused. This flips that to a hard gate: when true and at least
    | one active station exists, a punch with no location, or a location
    | outside every active station's radius, is rejected before it is ever
    | recorded.
    |
    | No active stations configured at all still never blocks — nothing is
    | configured to be "inside of" yet, and blocking every punch because ops
    | forgot to add a station would be a misconfiguration bricking attendance,
    | not a security control.
    |
    | Env-backed and default true so this can be flipped back to the legacy
    | flag-only behaviour in the field without a deploy.
    |
    */

    'geofence' => [
        'enforce' => env('ATTENDANCE_GEOFENCE_ENFORCE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Portal
    |--------------------------------------------------------------------------
    */

    'portal' => [
        // Punch attempts allowed per minute, per IP. A shared phone at a door is
        // one person every few seconds; anything far above that is a script.
        'rate_limit' => 20,

        // Seconds the result screen shows before the portal resets for the next
        // person.
        'reset_after' => 5,
    ],

];
