<?php
//
//namespace App\Http\Controllers;
//
//use Illuminate\Http\Request;
//use Stripe\Stripe;
//use Stripe\Checkout\Session as CheckoutSession;
//
//class SubscriptionController extends Controller
//{
//    public function start(Request $r)
//    {
//        // 実運用ではログインユーザーのテナントをここで取得
//        $tenant = auth()->user()?->tenant ?? null;
//
//        // Stripe秘密鍵
//        Stripe::setApiKey(config('services.stripe.secret'));
//
//        // プラン別にPriceIDを選択（Stripeダッシュボードで発行したIDを .env に）
//        $plan = $r->input('plan', 'pro'); // 'basic' | 'pro' | 'site'
//        $price = match ($plan) {
//            'basic' => env('PRICE_BASIC_3300'),
//            'site'  => env('PRICE_SITE_9900'),
//            default => env('PRICE_PRO_8800'),
//        };
//
//        $params = [
//            'mode' => 'subscription',
//            'line_items' => [
//                ['price' => $price, 'quantity' => 1],
//            ],
//            'success_url' => config('app.url').'/signup/success?session_id={CHECKOUT_SESSION_ID}',
//            'cancel_url'  => config('app.url').'/signup/cancel',
//            'metadata'    => [
//                'tenant_id' => $tenant?->id,
//                'plan'      => $plan,
//            ],
//        ];
//
//        // 既存Customerがあれば紐づけ、無ければ自動作成
//        if ($tenant?->stripe_customer_id) {
//            $params['customer'] = $tenant->stripe_customer_id;
//        } else {
//            $params['customer_creation'] = 'always'; // セッション完了時にcustomerが作られる
//        }
//
//        $session = CheckoutSession::create($params);
//
//        // フロントはこのURLへリダイレクト
//        return response()->json(['url' => $session->url]);
//    }
//}


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Checkout\Session as CheckoutSession;

class SubscriptionController extends Controller
{
    public function start(Request $r)
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $plan = $r->input('plan', 'pro'); // 'basic'|'pro'|'site'
        $price = match ($plan) {
            'basic' => env('PRICE_BASIC_3300'),
            'site' => env('PRICE_SITE_9900'),
            default => env('PRICE_PRO_8800'),
        };

        $params = [
            'mode' => 'subscription',
            'line_items' => [
                ['price' => $price, 'quantity' => 1],
            ],
            'success_url' => config('app.url') . '/signup/success?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => config('app.url') . '/signup/cancel',
            'metadata' => ['plan' => $plan],
        ];

        // 既存Customerがあれば紐づけ（将来: テナントモデルに保持）
        // if ($tenant?->stripe_customer_id) $params['customer'] = $tenant->stripe_customer_id;
        // else $params['customer_creation'] = 'always';

        $session = CheckoutSession::create($params);
        return response()->json(['url' => $session->url]);
    }
}
