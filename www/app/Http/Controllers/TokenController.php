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
     * ガチの本番用のもの
     */
    public function exchange(Request $r) {
        $role = $r->input('role'); // 'host' | 'guest'
        $code = $r->input('code');

        $col = $role === 'host' ? 'host_code' : 'guest_code';
        $row = Reservation::where($col, $code)->first();

        if (!$row) return response()->json(['error'=>'invalid_code'], 403);
        if ($row->status !== 'paid') return response()->json(['error'=>'payment_required'], 402);

        $identity = $role.'_'.substr($code, -6);

        $claims = [
            'iss'   => env('LIVEKIT_API_KEY'),
            'sub'   => $identity,
            'name'  => $identity,
            'nbf'   => time()-30,
            'exp'   => time()+3600,
            'video' => ['roomJoin'=>true, 'room'=>$row->room_name, 'canPublish'=>true, 'canSubscribe'=>true],
        ];
        $jwt = JWT::encode($claims, env('LIVEKIT_API_SECRET'), 'HS256');

        return response()->json([
            'token'      => $jwt,
            'identity'   => $identity,
            'room'       => $row->room_name,
            'livekit_url'=> env('LIVEKIT_HOST'),
        ]);
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
