<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mobile / kiosk API secrets
    |--------------------------------------------------------------------------
    |
    | These guard the unauthenticated `/api/*` endpoints the companion mobile
    | apps use (attendance kiosk, DTR sync, event scanner). They were previously
    | hardcoded in several controllers and the QR helper; they now live here so
    | they can be rotated from the environment without a code change, and so the
    | secret is defined in exactly one place.
    |
    | DEFAULTS: the fallbacks below are the values the deployed clients already
    | use, so nothing breaks if the env vars are unset. For a real production
    | deployment you SHOULD set them in .env and rotate the passcode — see
    | .env.example. Rotating the crypto key, however, invalidates every printed
    | employee QR card and must be coordinated with a re-issue.
    |
    */

    // Shared symmetric key for the short QR tokens (employee id <-> badge token).
    // NOTE: the cipher is AES-128-ECB for backward compatibility with tokens
    // already printed on physical cards and baked into the mobile app. Do not
    // change the cipher without re-issuing every card.
    'crypto' => [
        'key'    => env('API_CRYPTO_KEY', 'fA7xB93kL0pTzWmQ'),
        'cipher' => env('API_CRYPTO_CIPHER', 'AES-128-ECB'),
    ],

    // Static shared secret the event API (list / login / logs) requires. Passed
    // by the mobile client and checked in constant time by the
    // `event.passcode` middleware. Rotate this in production.
    'event_passcode' => env('EVENT_API_PASSCODE', '$2a$12$mWBPFC966rwEZ6V2DxtTsex4ZqvG7.fTiJ52WDHMRM6dG56wO2n0O'),

];
