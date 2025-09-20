<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use Stripe\Stripe;
use Stripe\Webhook;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
class StripeController extends Controller
{
    public function signupAndCheckout(Request $r)
    {
        $v = $r->validate([
            'name'     => ['required','string','max:255'],
            'email'    => ['required','email','max:255'],
            'password' => ['required','string','min:8'],
        ]);

        $user = User::where('email', $v['email'])->first();
        if ($user) {
            if (! Hash::check($v['password'], $user->password)) {
                return response()->json(['message' => 'このメールは登録済みです。パスワードが違います。'], 422);
            }
        } else {
            $user = User::create([
                'name'     => $v['name'],
                'email'    => $v['email'],
                'password' => Hash::make($v['password']),
            ]);
        }

        $secret  = config('services.stripe.secret');
        $priceId = env('STRIPE_PRICE_ID');
        if (! $secret || ! $priceId) {
            return response()->json(['message' => 'Stripe設定が未完了（SECRETまたはPRICE）'], 500);
        }

        $stripe = new \Stripe\StripeClient($secret);

        if (! $user->stripe_customer_id) {
            $cust = $stripe->customers->create([
                'email' => $user->email,
                'name'  => $user->name,
            ]);
            $user->stripe_customer_id = $cust->id;
            $user->save();
        }

        $front   = rtrim(env('APP_FRONTEND_URL', 'http://localhost:5176'), '/');
        $session = $stripe->checkout->sessions->create([
            'mode'       => 'subscription',
            'customer'   => $user->stripe_customer_id,
            'line_items' => [[ 'price' => $priceId, 'quantity' => 1 ]],
            'success_url'=> $front.'/billing/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $front.'/billing/cancel',
            'allow_promotion_codes' => true,
            'client_reference_id'   => (string)$user->id,
            'metadata'              => ['user_id' => (string)$user->id],
        ]);

        return response()->json(['url' => $session->url]);
    }
    public function handle(Request $r){
        $event = \Stripe\Event::constructFrom($r->all());

        if ($event->type === 'payment_intent.succeeded') {
            $pi = $event->data->object;
            $res = \App\Models\Reservation::where('stripe_payment_intent_id',$pi->id)->first();
            if ($res && $res->status !== 'paid') {
                $res->status = 'paid';
                $res->save();
                // 必要ならメール通知
            }
        }
        return response('ok', 200);
    }
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
}
