<?php

return [
    'demo_accounts' => [
        'admin' => [
            'name' => env('PORTAL_ADMIN_NAME', 'Portal Administrator'),
            'email' => env('PORTAL_ADMIN_EMAIL', 'etomet2patrick@gmail.com'),
            'password' => env('PORTAL_ADMIN_PASSWORD'),
        ],
        'student' => [
            'name' => env('PORTAL_STUDENT_NAME', 'Demo Student'),
            'email' => env('PORTAL_STUDENT_EMAIL', 'etopat2@gmail.com'),
            'password' => env('PORTAL_STUDENT_PASSWORD'),
        ],
    ],
];
