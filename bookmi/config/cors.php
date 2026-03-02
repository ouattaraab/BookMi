<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    // IMPORTANT: Set CORS_ALLOWED_ORIGINS in .env to a comma-separated list of
    // allowed domains (e.g. "https://bookmi.click,https://app.bookmi.click").
    // Leaving it as '*' is incompatible with supports_credentials=true in
    // modern browsers and must not be used in production.
    'allowed_origins' => env('CORS_ALLOWED_ORIGINS', '*') === '*'
        ? ['*']
        : explode(',', env('CORS_ALLOWED_ORIGINS', '')),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'Authorization', 'Accept', 'X-Requested-With'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => true,

];
