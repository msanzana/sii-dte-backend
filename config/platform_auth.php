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
];
