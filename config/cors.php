<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5000',
        'http://localhost:3000',
        // Thêm domain của ngrok
        'https://0cba-2405-4802-8010-b340-f44e-bd24-cbf3-1e2b.ngrok-free.app'
    ],

    'allowed_origins_patterns' => [
        // Thêm pattern cho các domain ngrok động
        '#^https:\/\/[a-z0-9-]+\.ngrok-free\.app$#'
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];