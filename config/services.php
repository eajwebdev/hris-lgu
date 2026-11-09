<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_CLIENT_CALLBACK'),
    ],

    'foxit' => [
        'api_key' => env('FOXIT_API_KEY'),
    ],

    'cpt_sync' => ['token' => env('CPT_SYNC_TOKEN')],

    // EAJ-Face-AI microservice used by FaceAIService. The service types its
    // base URL as a string, so a missing entry was a TypeError on
    // construction instead of a clear "service offline".
    'face_ai' => [
        'base_url' => env('FACE_AI_URL', 'http://127.0.0.1:8000/api'),
        'key' => env('FACE_AI_KEY', ''),
        'timeout' => (int) env('FACE_AI_TIMEOUT', 10),
    ],
];

