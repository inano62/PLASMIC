<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Reservation;

class ReservationController extends Controller
{
    public function create(Request $r) {
        $reservation = Reservation::create([
//            'room_name' => 'rm_'.Str::random(8),
            'room_name'  => 'demo',
            'host_code' => 'h_'.Str::random(24),
            'guest_code'=> 'g_'.Str::random(24),
            'status'    => 'paid',
        ]);
        // フロントURLを生成
        $origin  = frontend_origin(); // さっき作った helper でOK
        $hostUrl = $origin."/join/host/{$reservation->host_code}";
        $guestUrl= $origin."/join/guest/{$reservation->guest_code}";

        return response()->json([
            'id'        => $reservation->id,
            'room'      => $reservation->room_name,
            'host_url'  => $hostUrl,
            'guest_url' => $guestUrl,
        ]);
    }

    public function show(string $id) {
        $res = Reservation::findOrFail($id);
        if ($res->status !== 'paid') {
            return response()->json(['error' => 'payment_required'], 402);
        }
        $origin = frontend_origin(); // helpers.phpのやつ
        return response()->json([
            'id'       => $res->id,
            'room'     => $res->room_name,
            'host_url' => $origin."/join/host/{$res->host_code}",
            'guest_url'=> $origin."/join/guest/{$res->guest_code}",
        ]);
    }
}
