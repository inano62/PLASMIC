<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::post('/stripe/webhook', [\App\Http\Controllers\StripeWebhookController::class,'handle']);
