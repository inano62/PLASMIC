<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::post('/stripe/webhook', [\App\Http\Controllers\StripeWebhookController::class,'handle']);
Route::get('/api/livekit/token', function (Request $r) {
    $room = $r->query('room', 'test-room');
    $identity = $r->query('identity', 'user-'.bin2hex(random_bytes(3)));

    $apiKey = 'k1';
    $apiSecret = 'supersecret1234567890supersecret1234';

    $at = new AccessToken($apiKey, $apiSecret, ['identity' => $identity, 'ttl' => 3600]);
    $grant = (new VideoGrant())->setRoomJoin(true)->setRoom($room);
    $at->addGrant($grant);

    return response()->json(['token' => (string)$at->toJwt()]);
});
