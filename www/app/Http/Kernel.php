<?php
// app/Http/Kernel.php
namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    // これ！
    protected $middlewareAliases = [
        // 既存のエイリアス群...
        'site.entitled' => \App\Http\Middleware\EnsureSiteBuilderEntitled::class,
    ];
}
