<?php

return [
    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'login', 'logout', 'register',
    ],
    'allowed_methods' => ['*'],
    'allowed_headers' => ['*'],

    // フロントのオリジンだけ明示
    'allowed_origins' => ['http://localhost:5176'],

    // ここは空配列に！(いま書いてある正規表現は消す)
    'allowed_origins_patterns' => ['*'],

    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
