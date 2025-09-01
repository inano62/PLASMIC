<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\TokenController;
use App\Http\Controllers\StripeController;

Route::post('/rooms/issue', [RoomController::class, 'issue']);
Route::post('/reservations', [ReservationController::class, 'create']);
Route::get('/availability', [ReservationController::class, 'availability']);
Route::post('/pay/checkout', [StripeController::class, 'createCheckout']);
Route::get('/pay/confirm',  [StripeController::class, 'confirm']);
Route::post('/dev/token', [TokenController::class, 'devToken']);
Route::post('/pay/checkout/{id}', [StripeController::class, 'createCheckout']); // returns {url}
Route::post('/stripe/webhook', [StripeController::class, 'webhook']);
Route::post('/reservations', [ReservationController::class, 'create']);       // 予約作成（未払い）
Route::get('/reservations/{id}', [ReservationController::class, 'show']);     // 支払い後にURLを取得
Route::post('/pay/checkout/{id}', [StripeController::class, 'createCheckout']); // Stripeへ飛ばす
Route::post('/webhooks/stripe', [StripeController::class, 'webhook']);        // 決済完了でpaid化
Route::post('/exchange-token', [TokenController::class, 'exchange']);         // Join時のトークン発行（本番用）
Route::post('/exchange-token', [TokenController::class, 'exchange']); // role+code → LiveKit JWT
