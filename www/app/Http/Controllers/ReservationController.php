<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Reservation;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class ReservationController extends Controller
{
    public function createForSlot(Request $r) {
        $r->validate([
            'tenant_id' => 'required|integer',
            'timeslot_id' => 'required|integer',
            'customer_name' => 'required|string',
            'customer_email'=> 'required|email',
            'amount' => 'required|integer|min:100', // 例: 3000
        ]);

        $tenantId = (int)$r->tenant_id;
        $slotId   = (int)$r->timeslot_id;
        $amount   = (int)$r->amount;

        return DB::transaction(function() use($tenantId,$slotId,$amount,$r){
            /** @var Timeslot $slot */
            $slot = Timeslot::lockForUpdate()->findOrFail($slotId);
            if ($slot->tenant_id !== $tenantId || $slot->status !== 'open') {
                return response()->json(['error'=>'slot_unavailable'], 409);
            }

            // 予約行作成（未払い）
            $reservation = Reservation::create([
                'tenant_id' => $tenantId,
                'room_name' => 'rm_'.Str::random(8),
                'host_code' => 'h_'.Str::random(24),
                'guest_code'=> 'g_'.Str::random(24),
                'status'    => 'pending',
                'start_at'  => $slot->start_at,
                'end_at'    => $slot->end_at,
                'amount'    => $amount,
            ]);

            $slot->status = 'reserved';
            $slot->reservation_id = $reservation->id;
            $slot->save();

            // Stripe PaymentIntent（Connect・10%手数料）
            $tenant = \App\Models\Tenant::findOrFail($tenantId);
            $account = $tenant->stripe_connect_id;

            $pi = \Stripe\PaymentIntent::create([
                'amount' => $amount,
                'currency' => 'jpy',
                'payment_method_types' => ['card'],
                'application_fee_amount' => (int)round($amount * 0.10),
                'transfer_data' => ['destination' => $account],
                'on_behalf_of'  => $account,
                'metadata' => ['reservation_id' => $reservation->id],
            ]);

            $reservation->stripe_payment_intent_id = $pi->id;
            $reservation->save();

            return response()->json([
                'reservation_id' => $reservation->id,
                'client_secret'  => $pi->client_secret,
            ]);
        });
    }
    public function create(Request $r)
    {
        // 実際は「どのテナント（士業さん）か」を解決する処理を入れる
        $tenant = auth()->user()?->tenant ?? null;

        $amount  = (int) $r->input('amount', 8800); // JPY (税込)
        $room    = $r->input('room', 'demo');

        // 1) 予約レコードを pending で作成
        $reservation = Reservation::create([
            'tenant_id'  => $tenant?->id,
            'client_id'  => null,
            'room_name'  => $room,
            'host_code'  => 'h_'.Str::random(24),
            'guest_code' => 'g_'.Str::random(24),
            'status'     => 'pending',
            'amount'     => $amount,
            'currency'   => 'jpy',
            'starts_at'  => $r->input('starts_at'),
            'ends_at'    => $r->input('ends_at'),
        ]);

        // 2) PaymentIntent 作成（Connect: 10%手数料 / destination = プロの口座へ）
        Stripe::setApiKey(config('services.stripe.secret'));

        $params = [
            'amount'                     => $amount,
            'currency'                   => 'jpy',
            'automatic_payment_methods'  => ['enabled' => true],
            'metadata'                   => ['reservation_id' => (string) $reservation->id],
        ];

        if ($tenant?->stripe_connect_id) {
            $params['application_fee_amount'] = (int) round($amount * 0.10);
            $params['transfer_data']          = ['destination' => $tenant->stripe_connect_id];
            $params['on_behalf_of']           = $tenant->stripe_connect_id;
        }

        $pi = PaymentIntent::create($params);

        // 3) クライアントへ client_secret を返す（Stripe.js で支払い確定）
        return response()->json([
            'reservation_id' => $reservation->id,
            'client_secret'  => $pi->client_secret,
        ]);
    }

    public function show(string $id)
    {
        $res = Reservation::findOrFail($id);
        if ($res->status !== 'paid') {
            return response()->json(['error' => 'payment_required'], 402);
        }
        $origin = frontend_origin(); // helpers.php のやつ
        return response()->json([
            'id'        => $res->id,
            'room'      => $res->room_name,
            'host_url'  => $origin."/join/host/{$res->host_code}",
            'guest_url' => $origin."/join/guest/{$res->guest_code}",
        ]);
    }
}
