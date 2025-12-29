<?php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'broadcasting/auth', 'storage/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://web-dad-group-5-172.22.21.253.sslip.io', // O teu domínio de produção/k8s
        'http://localhost:5173', // O teu frontend local (Vite)
        'http://localhost',      // Por precaução
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
