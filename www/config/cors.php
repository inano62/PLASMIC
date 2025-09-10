<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CORS Paths
    |--------------------------------------------------------------------------
    | CORS を有効にするパス。API と Sanctum の CSRF 発行を対象にします。
    */
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    /*
    |--------------------------------------------------------------------------
    | Allowed Methods / Headers / Origins
    |--------------------------------------------------------------------------
    */
    'allowed_methods' => ['*'],
    'allowed_headers' => ['*'],

    // 開発中に使うオリジン（Vite と Laravel）
    'allowed_origins' => [
        'http://localhost:5176',
        'http://127.0.0.1:5176',
        'http://localhost:8000',
        'http://127.0.0.1:8000',
    ],

    // 正規表現で許可したい場合はここ（例：localhost/127.0.0.1 の http/https 全許可）
    // 'allowed_origins_patterns' => ['#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#'],
    'allowed_origins_patterns' => [],

    // レスポンスで露出させたいヘッダがあれば列挙
    'exposed_headers' => [],

    // プリフライトのキャッシュ秒数（1時間）
    'max_age' => 3600,

    // Cookie ベース認証（Sanctum）なら true
    'supports_credentials' => true,
];
