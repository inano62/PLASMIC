<?php

use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicSiteController;

require __DIR__ . '/auth.php';

// …この上のデバッグ/ログイン系はそのまま…

Route::middleware('auth')->group(function () {
    Route::get('/admin/site',           [SiteController::class, 'builder'])->name('site.builder')->middleware('site.entitled');
    Route::get('/admin/site/pay',       [SiteController::class, 'paywall'])->name('site.paywall');
    Route::post('/admin/site/checkout', [SiteController::class, 'checkout'])->name('site.checkout');
    Route::get('/admin/site/thanks',    [SiteController::class, 'thanks'])->name('site.thanks');
});

if (app()->environment('local')) {
    // 開発中: React を Vite(5176) で配信
    Route::get('/s/{slug}/{any?}', function () {
        $uri = request()->getRequestUri(); // /s/slug/... (+ query)
        return redirect()->away("http://localhost:5176{$uri}");
    })->where('any', '.*');
} else {
    // 本番/ステージング: Laravel 側で出す（published_html or Blade）
    Route::get('/s/{slug}/{any?}', [PublicSiteController::class, 'show'])
        ->where('any', '.*');
}
// サイト情報JSON
Route::get('/api/public/sites/{slug}', [PublicSiteController::class, 'site']);
Route::get('/api/public/sites/{slug}/page', [PublicSiteController::class, 'page']);
