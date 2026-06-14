<?php
return [
    'jwt' => [
        'secret' => env('PLATFORM_AUTH_JWT_SECRET', ''),
        'issuer' => env('PLATFORM_AUTH_JWT_ISSUER', 'sii-dte-backend'),
        'audience' => env('PLATFORM_AUTH_JWT_AUDIENCE', 'sii-dte-platform'),
        'algorithm' => 'HS256',
        'ttl_seconds' => [
            'pre_company' => (int) env('PLATFORM_AUTH_JWT_PRECOMPANY_TTL', 600),
            'access' => (int) env('PLATFORM_AUTH_JWT_ACCESS_TTL', 28800),
        ],
    ],

    'refresh_tokens' => [
        'ttl_seconds' => (int) env('PLATFORM_AUTH_REFRESH_TTL', 2592000),
        'rotate_on_refresh' => filter_var(env('PLATFORM_AUTH_REFRESH_ROTATE', true), FILTER_VALIDATE_BOOLEAN),
    ],

    'password_reset' => [
        'ttl_seconds' => (int) env('PLATFORM_AUTH_PASSWORD_RESET_TTL', 3600),
        'frontend_url' => env('PLATFORM_AUTH_PASSWORD_RESET_FRONTEND_URL', 'http://localhost:5173/reset-password'),
    ],
];
