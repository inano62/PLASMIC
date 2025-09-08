<?php

use App\Http\Controllers\ReservationController;
use App\Http\Controllers\TokenController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\TimeslotController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\StripeWebhookController;



// routes/web.php or routes/api.php
Route::middleware(['auth', 'can:site.build'])->group(function () {
    Route::post('/pay/site-pro', [\App\Http\Controllers\BillingController::class,'siteProCheckout']);
    Route::get('/admin/site', [\App\Http\Controllers\SiteController::class,'index']);
    Route::post('/admin/site/publish', [\App\Http\Controllers\SiteController::class,'publish']);
});
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/me', fn(\Illuminate\Http\Request $r) => $r->user());
    Route::get('/sitebuilder/status', [SiteBuilderController::class, 'status']);
    Route::post('/sitebuilder/checkout', [SiteBuilderController::class, 'checkout']);
});
Route::middleware('auth')->group(function () {
    // 課金状況の問い合わせ（フロントのゲート用・JSON）
    Route::get('/api/sitebuilder/status', [SiteBuilderController::class, 'status']);

    // チェックアウト作成（Stripe セッション）
    Route::post('/api/sitebuilder/checkout', [SiteBuilderController::class, 'checkout']);

    // 決済戻り（success/cancel）
    Route::get('/admin/site/thanks', [SiteBuilderController::class, 'thanks'])->name('site.thanks');
    Route::get('/admin/site/pay',   [SiteController::class, 'paywall'])->name('site.paywall');

    // ビルダー本体（**課金者のみ**）
    Route::middleware('site.entitled')->group(function () {
        Route::get('/admin/site', [SiteController::class, 'builder'])->name('site.builder');
        Route::post('/admin/site/publish', [SiteController::class, 'publish']);
    });
});
// テナント解決用（slug でも id でも受けられる）
Route::get('/ping', fn() => ['ok' => true, 'time' => now()->toIso8601String()]);

Route::prefix('public')->group(function () {
    Route::get('/tenants', [\App\Http\Controllers\PublicController::class, 'tenants']);
    Route::get('/tenants/{id}/pros', [PublicController::class, 'pros']);       // ← 先生一覧
    Route::get('/tenants/{id}/slots', [TimeslotController::class, 'listOpen']); // ← 空き枠
// routes/api.php
    Route::get('/tenants/{tenant}/slots', [\App\Http\Controllers\AppointmentController::class, 'publicSlots']);
    // routes/api.php
    Route::get('/tenants/resolve', [\App\Http\Controllers\PublicController::class, 'resolveTenant']);


});
// tenants 配下の疎通テスト用 2 ルート
Route::prefix('tenants/{tenant}')->group(function () {
    // 空き枠
    Route::get('/availability', [TimeslotController::class, 'listOpenForTenant']);
    Route::post('/appointments', [AppointmentController::class, 'storeForTenant']); // 予約
    // 自分の直近予約（visitor_id 必須）
    Route::get('/my/appointments', [AppointmentController::class, 'myForTenant']);

    // 予約作成（tenant_id をサーバ側で付与）
    Route::match(['GET','POST'],'/appointments', [AppointmentController::class, 'storeForTenant']);

});
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);
Route::get('/public/tenants/{id}/pros', [PublicController::class, 'pros']);
// 顧客 upsert（問い合わせ/予約の最初に呼ぶ）
Route::post('/clients/upsert', [\App\Http\Controllers\ClientController::class,'upsert'])
    ->name('clients.upsert');
// 予約（枠と顧客を紐づけ、Stripeへ）
Route::post('/reservations', [ReservationController::class, 'create']);    // 未払い
Route::post('/pay/checkout/{id}', [StripeController::class, 'createCheckout']);
Route::post('/stripe/webhook', [StripeController::class, 'webhook']);      // paid → appointment.booked
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);
// 面談（リンク/チケット）
Route::post('/appointments', [AppointmentController::class, 'store']); // 公開フォームからお客さんが使う
Route::get('/appointments/{id}', [AppointmentController::class, 'show']);  // room/status
Route::post('/appointments/{id}/ticket', [AppointmentController::class, 'issueTicket']); // {ticket}
Route::get('/appointments/upcoming', [AppointmentController::class, 'upcoming']);        // 士業ダッシュボード
Route::get('/appointments/nearby', [AppointmentController::class, 'nearby']);          // 直近60分

// routes/api.php
Route::post('/appointments/instant', [\App\Http\Controllers\AppointmentController::class,'instant'])
    ->name('appointments.instant');

// LiveKit トークン
Route::post('/dev/token', [TokenController::class, 'exchange']);
Route::get('/wait/resolve', [\App\Http\Controllers\AppointmentController::class, 'resolve']);
Route::prefix('tenants/{tenant:slug}')->group(function () {
    // 空き枠（既存 listOpen のラッパ）
    Route::get('/availability', [TimeslotController::class, 'listOpenByTenant']);

    // 直近の自分の予約（必要なら）
    Route::get('/my/appointments', [AppointmentController::class, 'myByVisitor']);

    // 予約作成（tenant_id はURLから強制）
    Route::post('/appointments', [AppointmentController::class, 'storeForTenant']);
});
