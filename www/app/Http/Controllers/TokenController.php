<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Firebase\JWT\JWT;
use App\Models\Reservation;
use Firebase\JWT\Key;
use Illuminate\Support\Str;

class TokenController extends Controller
{
    /***
     *  開発用: LiveKitのダミーJWTを返す（roomは必須）
     */
    public function exchange(Request $r) {
        $room = $r->input('room', 'test-room');
        $identity = $r->input('identity', 'user-'.bin2hex(random_bytes(3)));

        $apiKey    = env('LIVEKIT_API_KEY', 'devkey');
        $apiSecret = env('LIVEKIT_API_SECRET', 'devsecret');
        $url       = env('LIVEKIT_URL'); // 無ければフロントのデフォルトを利用

        $payload = [
            'iss'   => $apiKey,
            'sub'   => $identity,
            'iat'   => time(),
            'exp'   => time() + 3600,
            'video' => [
                'roomJoin'     => true,
                'room'         => $room,
                'canPublish'   => true,
                'canSubscribe' => true,
            ],
        ];

        $jwt = JWT::encode($payload, $apiSecret, 'HS256');

        return response()->json(['token'=>$jwt, 'url'=>$url]); // 200

    }

    public function devToken(Request $r) {
        $identity = $r->input('identity', 'guest_'.Str::random(6));
        $room     = $r->input('room', 'demo');

        $claims = [
            'iss'   => env('LIVEKIT_API_KEY'),
            'sub'   => $identity,
            'name'  => $identity,
            'nbf'   => time() - 30,
            'exp'   => time() + 3600,
            'video' => [
                'roomJoin'    => true,
                'room'        => $room,
                'canPublish'  => true,
                'canSubscribe'=> true,
            ],
        ];

        $jwt = JWT::encode($claims, env('LIVEKIT_API_SECRET'), 'HS256');

        return response()->json([
            'token'      => $jwt,
            'identity'   => $identity,
            'room'       => $room,
            'livekit_url'=> env('LIVEKIT_HOST'),
        ]);
    }

}
