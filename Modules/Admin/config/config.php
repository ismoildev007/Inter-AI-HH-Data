<?php

return [
    'name' => 'Admin',
    'seeder' => [
        'email' => env('ADMIN_EMAIL', 'admin@gmail.com'),
        'password' => env('ADMIN_PASSWORD', 'password'),
    ],
];
