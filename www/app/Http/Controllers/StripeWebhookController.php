<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use App\Models\Reservation;

class StripeWebhookController extends Controller {
    public function handle(Request $r) {
        Stripe::setApiKey(config('services.stripe.secret'));
        $event = \Stripe\Event::constructFrom($r->all());

        if ($event->type === 'payment_intent.succeeded') {
            $pi = $event->data->object;
            $resId = $pi->metadata->reservation_id ?? null;
            if ($resId && ($res = Reservation::find($resId))) {
                $res->status = 'paid';
                $res->stripe_payment_intent_id = $pi->id;
                $res->save();
            }
        }
        return response()->noContent();
    }
}
