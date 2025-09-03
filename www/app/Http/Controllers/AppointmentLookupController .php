<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Reservation;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class AppointmentLookupController  extends Controller
{
    public function lookup(Request $r) {
        $idv = trim((string)$r->input('identifier',''));
        if ($idv === '') return response()->json(['message'=>'identifier required'], 422);

        // 電話は数字のみに正規化して検索
        $digits = preg_replace('/\\D+/', '', $idv);

        $q = Reservation::query()->where('status','!=','canceled');
        $q->when(filter_var($idv, FILTER_VALIDATE_EMAIL), fn($qq)=>$qq->where('requester_email',$idv));
        $q->when($digits && strlen($digits)>=9, fn($qq)=>$qq->orWhere('requester_phone','like','%'.$digits.'%'));
        $q->orWhere('requester_name','like','%'.$idv.'%');

        $res = $q->orderByDesc('start_at')->first();
        if (!$res) return response()->json(['message'=>'not found'], 404);

        // 再発行（短命JWT）
        $ticket = WaitTicket::create([
            'id'             => (string) Str::uuid(),
            'reservation_id' => $res->id,
            'token_jwt'      => $this->issueShortJwt($res->id),
            'otp_code'       => sprintf('%06d', random_int(0, 999999)),
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        return response()->json([
            'latest' => [
                'id'            => $res->id,
                'start_at'      => $res->start_at->toIso8601String(),
                'purpose_title' => $res->purpose_title,
                'clientJoinPath'=> "/wait?ticket={$ticket->token_jwt}",
            ]
        ]);
    }
}
