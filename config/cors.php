<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cấu hình Cross-Origin Resource Sharing (CORS)
    |--------------------------------------------------------------------------
    |
    | Đây là cấu hình cho phép truy cập từ các domain khác đến API của bạn
    | Trong trường hợp này, chúng ta cho phép localhost:5000 truy cập
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['http://localhost:5000'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];