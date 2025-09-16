<?php

return [
    'paths' => ['api/*','sanctum/csrf-cookie','login','logout'],
    'allowed_methods' => ['*'],
    'allowed_headers' => ['*'],
    'allowed_origins' => ['http://localhost:5176'],
    'allowed_origins_patterns' => ['#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#'],
    'supports_credentials' => true,
];
