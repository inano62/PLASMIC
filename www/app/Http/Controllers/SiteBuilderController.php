<?php
// app/Http/Controllers/SiteBuilderController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Stripe\Stripe;

class SiteBuilderController extends Controller
{
    public function status(Request $r)
    {
        return ['entitled' => Gate::allows('site.build', $r->user())];
    }

    public function checkout(Request $r)
    {
        $u = $r->user();
        Stripe::setApiKey(config('services.stripe.secret'));

        $session = \Stripe\Checkout\Session::create([
            'mode' => 'payment',
            'line_items' => [[
                // .env に価格IDを入れてください
                'price' => env('STRIPE_PRICE_SITE_PRO'), // 例: price_XXXXXXXX
                'quantity' => 1,
            ]],
            'customer' => $u->stripe_customer_id ?: null,
            'customer_creation' => $u->stripe_customer_id ? 'always' : 'if_required',
            'success_url' => url('/admin/site/thanks?session_id={CHECKOUT_SESSION_ID}'),
            'cancel_url'  => url('/admin/site/pay?canceled=1'),
            'metadata'    => ['user_id' => $u->id],
        ]);

        return ['url' => $session->url];
    }

    public function thanks(Request $r)
    {
        $sid = $r->query('session_id');
        if (!$sid) {
            return redirect()->route('site.paywall')->with('error','session_id がありません');
        }

        Stripe::setApiKey(config('services.stripe.secret'));
        $sess = \Stripe\Checkout\Session::retrieve($sid);

        if ($sess->payment_status === 'paid') {
            $u = $r->user();
            $u->account_type = 'pro';
            if ($sess->customer && !$u->stripe_customer_id) {
                $u->stripe_customer_id = $sess->customer;
            }
            $u->save();

            return redirect()->route('site.builder');
        }

        return redirect()->route('site.paywall')->with('error','未決済です');
    }
}
