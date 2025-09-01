<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use Stripe\Stripe;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Webhook;

class StripeController extends Controller
{
    public function createCheckout(Reservation $reservation)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $origin = frontend_origin();
        $session = Checkout::create([
            'mode' => 'payment',
            'success_url' => $origin.'/reserve/success?rid='.$reservation->id,
            'cancel_url'  => $origin.'/reserve',
            'line_items' => [[
                'price_data' => [
                    'currency' => 'jpy',
                    'product_data' => ['name' => 'Video Session (30min)'],
                    'unit_amount' => 3000 * 100,
                ],
                'quantity' => 1,
            ]],
            'metadata' => ['reservation_id' => $reservation->id],
        ]);

        return response()->json(['checkout_url' => $session->url]);
    }

    public function webhook(Request $r) {
        $payload = $r->getContent();
        $sig = $r->header('Stripe-Signature');
        $secret = env('STRIPE_WEBHOOK_SECRET');

        try {
            $event = $secret
                ? Webhook::constructEvent($payload, $sig, $secret)
                : json_decode($payload);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'invalid payload'], 400);
        }

        if (($event->type ?? null) === 'checkout.session.completed') {
            $session = $event->data->object;
            $id = $session->metadata->reservation_id ?? null;
            if ($id && ($res = Reservation::find($id))) {
                $res->status = 'paid';
                $res->save();
            }
        }
        return response()->json(['ok' => true]);
    }
}
