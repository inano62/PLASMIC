<?php

use App\Models\Appointment;
use App\Models\Tenant;
//use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\TokenController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\TimeslotController;
use App\Http\Controllers\AppointmentController;
use Livekit\AccessToken;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Http\Request;


// テナント解決用（slug でも id でも受けられる）
Route::get('/ping', fn() => ['ok' => true, 'time' => now()->toIso8601String()]);

Route::prefix('public')->group(function () {
    Route::get('/tenants', [\App\Http\Controllers\PublicController::class, 'tenants']);
    Route::get('/tenants/{id}/pros', [PublicController::class, 'pros']);       // ← 先生一覧
    Route::get('/tenants/{id}/slots', [TimeslotController::class, 'listOpen']); // ← 空き枠
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
    // ★ デバッグ用：GET/POST どちらでもここに落として dd する
//    Route::match(['GET','POST'], '/appointments', function (Request $req, string|int $tenant) {
//        dd([
//            'tenant'  => $tenant,
//            'method'  => $req->method(),
//            'all'     => $req->all(),
//            'query'   => $req->query(),
//            'raw'     => $req->getContent(),
//            'headers' => $req->headers->all(),
//        ]);
//    });
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
