<?php
namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // 何も書かなくてOK（デフォのルーティングは bootstrap/app.php 側でやってる）
    }
}
