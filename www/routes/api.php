<?php

use Illuminate\Support\Facades\Route;
// routes/api.php
use Illuminate\Http\Request;
use LiveKit\AccessToken;
use LiveKit\VideoGrant;

Route::post('/busho', 'BushoController@post');
Route::post('/downloadable', 'DownloadableController@post');
Route::post('/import', 'ImportController@post');
Route::post('/logout', 'LogoutController@post');
Route::post('/me', 'MeController@post');
Route::post('/segmentset', 'SegmentsetController@post');


Route::get('/livekit-token', function (Request $req) {
    $room = $req->query('room', 'demo');
    $identity = $req->query('identity', 'user-'.uniqid());
    $at = new AccessToken(env('LIVEKIT_API_KEY','devkey'), env('LIVEKIT_API_SECRET','secret'));
    $at->setIdentity($identity);
    $at->addGrant(new VideoGrant([
        'room' => $room, 'roomJoin' => true, 'canPublish' => true, 'canSubscribe' => true,
    ]));
    return ['token' => $at->toJwt()];
});
