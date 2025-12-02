<?php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'broadcasting/auth'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://web-dad-group-5-172.22.21.253.sslip.io',
        'http://localhost:5173', // localhost frontend dev
        //'http://localhost:8000', // A API direta
        //'http://localhost',      //
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
