<?php
// app/Http/Controllers/BillingController.php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\StripeClient;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Webhook;

class BillingController extends Controller
{
    public function siteProCheckout(Request $req)
    {
        $u = $req->user();
        abort_unless($u->canBuildSite(), 403);

        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));

        if (!$u->stripe_customer_id) {
            $cust = $stripe->customers->create([
                'email' => $u->email,
                'name'  => $u->name,
            ]);
            $u->stripe_customer_id = $cust->id;
            $u->save();
        }

        $session = $stripe->checkout->sessions->create([
            'mode'       => 'subscription', // もし買い切りにするなら 'payment'
            'customer'   => $u->stripe_customer_id,
            'line_items' => [[ 'price' => env('PRICE_SITE_PRO'), 'quantity' => 1 ]],
            'success_url'=> env('FRONTEND_ORIGIN').'/admin/site?upgraded=1',
            'cancel_url' => env('FRONTEND_ORIGIN').'/admin/site?canceled=1',
        ]);

        return response()->json(['url' => $session->url]);
    }
    public function checkout(Request $request) {
        $user = $request->user();
        abort_unless($user, 401);

        $stripe = new StripeClient(config('services.stripe.secret') ?? env('STRIPE_SECRET'));

        // 既存Customerを使い回し
        $customerId = $user->stripe_customer_id ?: null;
        if (!$customerId) {
            $customer = $stripe->customers->create([
                'email' => $user->email,
                'name'  => $user->name,
            ]);
            $customerId = $customer->id;
            $user->stripe_customer_id = $customerId;
            $user->save();
        }

        $priceId = $request->input('price_id', env('STRIPE_PRICE_ID'));
        $session = CheckoutSession::create([
            'mode' => 'subscription', // 都度課金なら 'payment'
            'customer' => $user->stripe_customer_id,
            'line_items' => [[ 'price' => $priceId, 'quantity' => 1 ]],
            'success_url' => env('APP_FRONTEND_URL').'/billing/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => env('APP_FRONTEND_URL').'/billing/cancel',
            'allow_promotion_codes' => true,
        ]);

        return response()->json(['url' => $session->url]);
    }

    public function thanks(Request $req) {
        $sid = $req->query('session_id');
        if (!$sid) return view('thanks', ['status' => 'canceled']);

        $stripe = new StripeClient(env('STRIPE_SECRET'));
        $session = $stripe->checkout->sessions->retrieve($sid, [
            'expand' => ['subscription', 'customer'],
        ]);

        $subscription = $session->subscription;   // object or id
        $customer     = $session->customer;
        $paid         = $session->payment_status === 'paid';

        // 画面用の情報
        return view('thanks', [
            'status'          => $paid ? 'paid' : 'pending',
            'session'         => $session,
            'subscription_id' => is_string($subscription) ? $subscription : $subscription->id,
            'customer_id'     => is_string($customer) ? $customer : $customer->id,
        ]);
    }
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $sig     = $request->header('Stripe-Signature');
        $secret  = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sig, $secret);
        } catch (\Throwable $e) {
            \Log::warning('stripe webhook signature failed', ['err' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 400);
        }

        switch ($event->type) {
            case 'checkout.session.completed': {
                $s = $event->data->object;

                $customerId     = is_string($s->customer) ? $s->customer : ($s->customer->id ?? null);
                $subscriptionId = is_string($s->subscription) ? $s->subscription : ($s->subscription->id ?? null);

                // 1) metadata / client_reference_id からユーザー特定
                $uid  = $s->metadata->user_id ?? $s->client_reference_id ?? null;
                $user = $uid ? User::find($uid) : null;

                // 2) それでも見つからなければ customer でひも付け
                if (!$user && $customerId) {
                    $user = User::where('stripe_customer_id', $customerId)->first();
                }

                if ($user) {
                    $user->stripe_customer_id    = $customerId ?: $user->stripe_customer_id;
                    $user->stripe_subscription_id = $subscriptionId ?: $user->stripe_subscription_id;
                    $user->subscription_status   = 'active';
                    $user->stripe_status         = 'active';
                    $user->save();
                } else {
                    \Log::warning('no user for checkout.session.completed', [
                        'uid' => $uid, 'customer' => $customerId
                    ]);
                }
                break;
            }

            case 'customer.subscription.created':
            case 'customer.subscription.updated':
            case 'customer.subscription.deleted': {
                $sub    = $event->data->object;
                $status = $sub->status; // active, trialing, canceled, past_due 等

                $user = User::where('stripe_subscription_id', $sub->id)->first()
                    ?: User::where('stripe_customer_id', $sub->customer)->first();

//                if ($user) {
                    $user->stripe_subscription_id = $sub->id;
                    $user->subscription_status    = $status;
                    $user->stripe_status          = $status;
                    $user->save();
//                } else {
//                    \Log::warning('no user for subscription event', [
//                        'sub' => $sub->id, 'customer' => $sub->customer
//                    ]);
//                }
//                break;
            }
        }

        return response()->noContent(); // 204
    }
    public function session(string $sid) {
        try {
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret')); // envよりconfig経由で統一
            $s = $stripe->checkout->sessions->retrieve($sid, [
                'expand' => ['subscription', 'customer', 'payment_intent'],
            ]);

            // customer
            $customerId = null;
            if (isset($s->customer)) {
                $customerId = is_string($s->customer) ? $s->customer : ($s->customer->id ?? null);
            }

            // subscription （null の可能性あり！）
            $subscriptionId = null;
            if (isset($s->subscription)) {
                $subscriptionId = is_string($s->subscription) ? $s->subscription : ($s->subscription->id ?? null);
            }

            return response()->json([
                'id'               => $s->id,
                'status'           => $s->payment_status,          // 'paid' / 'unpaid' / 'no_payment_required'
                'mode'             => $s->mode,                    // 'subscription' 等
                'customer_id'      => is_string($s->customer) ? $s->customer : ($s->customer->id ?? null),
                'subscription_id'  => is_string($s->subscription) ? $s->subscription : ($s->subscription->id ?? null),
                'amount_total'     => $s->amount_total ?? null,
                'currency'         => $s->currency ?? null,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Stripe session retrieve failed', ['sid'=>$sid, 'e'=>$e->getMessage()]);
            return response()->json([
                'message' => 'session lookup failed',
                'error'   => $e->getMessage(),
            ], 400);
        }
    }
}
