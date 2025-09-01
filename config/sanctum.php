<?php

return [

    'stateful' => explode(',', (string) env('SANCTUM_STATEFUL_DOMAINS', 'localhost,127.0.0.1,localhost:5173')),

    'guard' => ['web'],

    'expiration' => env('SANCTUM_EXPIRATION'),

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        'validate_cookies' => Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    ],
];

