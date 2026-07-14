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
