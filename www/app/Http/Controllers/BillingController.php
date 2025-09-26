<?php
// app/Http/Controllers/BillingController.php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\StripeClient;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Webhook;
use App\Services\SiteProvisioner;
use App\Models\Tenant;

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
            'client_reference_id' => (string)$user->id,             // ← 追加
            'metadata' => ['user_id' => (string)$user->id],         // ← 追加
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
    public function webhook(Request $request,SiteProvisioner $prov)
    {
        $secret = config('services.stripe.webhook_secret') ?? env('STRIPE_WEBHOOK_SECRET');
        $payload = $request->getContent();
        $sig     = $request->header('Stripe-Signature');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig, $secret);
        } catch (\Throwable $e) {
            return response('invalid signature', 400);
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object; // \Stripe\Checkout\Session

                // すでに処理済みならスキップ（冪等化は必要に応じてイベントID記録でもOK）
                $userId = (int)($session->client_reference_id ?? ($session->metadata->user_id ?? 0));
                if (!$userId) break;

                $user = User::find($userId);
                if (!$user) break;

                // Stripe customer を保存（初回のみ）
                if (!$user->stripe_customer_id && $session->customer) {
                    $user->stripe_customer_id = $session->customer;
                    $user->save();
                }

                // ★ ここでテナント & サイトを冪等に作成
                $tenant = Tenant::firstOrCreate(
                    ['owner_user_id' => $user->id], // 主キーの方針に合わせてキーを選ぶ
                    [
                        'display_name' => $user->name . ' 事務所',
                        'slug'         => $this->slugFromName($user->name), // 下の補助関数例
                        'region'       => null,
                        'type'         => null,
                    ]
                );

                // オーナー権限の付与（中間テーブルがある場合）
                if (method_exists($tenant, 'members')) {
                    $tenant->members()->syncWithoutDetaching([$user->id => ['role' => 'owner']]);
                }

                // サイト雛形を作成（冪等）
                $site = $prov->provisionForTenant($tenant);

                // 任意：published JSON をここで生成したければ Publisher を呼ぶ
                // app(SitePublisher::class)->publish($site);

                break;

            case 'customer.subscription.created':
            case 'customer.subscription.updated':
                // 必要なら購読状態を user/tenant に反映（active/canceled 等）
                break;
        }

        return response('ok', 200);
    }
    private function slugFromName(string $name): string
    {
        $base = \Str::slug($name, '-');               // 例: "山田太郎 事務所" → "shan-tang"
        $base = $base ?: ('office-'.\Str::random(6)); // すべて非ASCIIの保険
        $slug = $base;
        $i = 1;
        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }
        return $slug;
    }
    public function session(string $sid,SiteProvisioner $prov) {
        try {
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret')); // envよりconfig経由で統一
            $s = $stripe->checkout->sessions->retrieve($sid, [
                'expand' => ['subscription', 'customer', 'payment_intent'],
            ]);

            if (($site->payment_status ?? null) !== 'paid') {
                abort(409, 'not paid');
            }
            $userId = (int)($session->client_reference_id ?? ($session->metadata->user_id ?? 0));
            $user = User::findOrFail($userId);

            $tenant = Tenant::firstOrCreate(
                ['owner_user_id'=>$user->id],
                ['display_name'=>$user->name.' 事務所','slug'=>$this->slugFromName($user->name)]
            );
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
            $site = $prov->provisionForTenant($tenant);
            return response()->json([
                'id'               => $s->id,
                'status'           => $s->payment_status,          // 'paid' / 'unpaid' / 'no_payment_required'
                'mode'             => $s->mode,                    // 'subscription' 等
                'customer_id'      => is_string($s->customer) ? $s->customer : ($s->customer->id ?? null),
                'subscription_id'  => is_string($s->subscription) ? $s->subscription : ($s->subscription->id ?? null),
                'amount_total'     => $s->amount_total ?? null,
                'currency'         => $s->currency ?? null,
                'tenant_id'        => $tenant->only('id,slug'),
                'site'             => $site->only('id,slug'),
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
