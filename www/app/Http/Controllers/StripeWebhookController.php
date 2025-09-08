<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;

class StripeWebhookController extends Controller
{
    public function handle(Request $r)
    {
        // 署名検証
        $payload = $r->getContent();
        $sig     = $r->header('Stripe-Signature');
        $secret  = config('services.stripe.webhook_secret'); // .env: STRIPE_WEBHOOK_SECRET

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig, $secret);
        } catch (\Throwable $e) {
            return response('Invalid', 400);
        }
        switch ($event->type) {
            case 'checkout.session.completed':
                $sess     = $event->data->object; // \Stripe\Checkout\Session
                $customer = $sess->customer ?? null;
                if ($customer) {
                    \App\Models\User::where('stripe_customer_id', $customer)
                        ->where('account_type', '!=', 'admin')
                        ->update(['account_type' => 'pro']);
                }
                break;

            case 'payment_intent.succeeded':
                $pi    = $event->data->object;   // \Stripe\PaymentIntent
                $resId = $pi->metadata->reservation_id ?? null;
                if ($resId && ($res = Reservation::find($resId))) {
                    $res->status = 'paid';
                    $res->stripe_payment_intent_id = $pi->id;
                    $res->save();
                }
                break;
        }
        return response()->noContent();
    }
}
