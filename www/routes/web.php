<?php

use App\Http\Controllers\SiteController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

require __DIR__ . '/auth.php';

// …この上のデバッグ/ログイン系はそのまま…

Route::middleware('auth')->group(function () {
    Route::get('/admin/site',           [SiteController::class, 'builder'])->name('site.builder')->middleware('site.entitled');
    Route::get('/admin/site/pay',       [SiteController::class, 'paywall'])->name('site.paywall');
    Route::post('/admin/site/checkout', [SiteController::class, 'checkout'])->name('site.checkout');
    Route::get('/admin/site/thanks',    [SiteController::class, 'thanks'])->name('site.thanks');
});

// dev 環境だけ、/s/* を Vite (5176) に飛ばす
if (app()->environment('local')) {
    Route::get('/s/{slug}/{any?}', function () {
        $uri = request()->getRequestUri(); // /s/slug/... + ?query
        return redirect()->away("http://localhost:5176{$uri}");
    })->where('any', '.*');
}
// ← 本番は Laravel で /s/* を持たない（Nginx でフロントにフォールバックさせる）
