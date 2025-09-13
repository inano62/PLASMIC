<?php
// app/Http/Kernel.php
namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    protected $middlewareAliases = [
        // 既存のエイリアス群...
        'site.entitled' => \App\Http\Middleware\EnsureSiteBuilderEntitled::class,
        'api' => [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];
    protected $middleware = [
        // これがあること（Laravel10は Fruitcake、11は Illuminate でもOK）
        \Fruitcake\Cors\HandleCors::class,
        // or
        // \Illuminate\Http\Middleware\HandleCors::class,
    ];
}
