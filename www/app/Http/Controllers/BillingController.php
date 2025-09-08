<?php
// app/Http/Controllers/BillingController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
}
