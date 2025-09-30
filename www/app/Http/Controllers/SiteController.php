<?php
// app/Http/Controllers/SiteController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Site;
use Illuminate\Support\Str;

class SiteController extends Controller
{
    public function index(Request $r) {
        return Site::where('owner_id', $r->user()->id)->get();
    }
    public function store(Request $r) {
        $data = $r->validate([
            'title' => 'required|string|max:120',
            'slug'  => 'nullable|string|alpha_dash|unique:sites,slug',
        ]);
        $site = Site::create([
            'title' => $data['title'],
            'slug'  => $data['slug'] ?? Str::slug($data['title']),
            'owner_id' => $r->user()->id,
            'status' => 'draft',
        ]);
        return response()->json($site, 201);
    }
    public function publish(Request $req)
    {
        abort_unless($req->user()->hasPro(), 402, 'サイト公開には Pro が必要です'); // 402 Payment Required
        // …公開処理
        return response()->json(['ok'=>true]);
    }
    public function builder(Request $r)
    {
        // ここはミドルウェア通過後なのでビルダーをそのまま表示
//        return view('admin.site'); // 既存のビルダービュー
        return response('BUILDER OK', 200);
    }

    public function paywall(Request $r)
    {
        // ペイウォール画面（「購入」ボタンが /admin/site/checkout に POST）
        return view('admin.site_paywall');
    }

    public function checkout(Request $r)
    {
        $u = $r->user();

        Stripe::setApiKey(config('services.stripe.secret'));

        // 単発 ¥19,800 の価格ID（.env → config/services.php に登録）
        $price = config('services.stripe.site_price_id'); // 例: price_XXXX

        $session = \Stripe\Checkout\Session::create([
            'mode' => 'payment',
            'line_items' => [[ 'price' => $price, 'quantity' => 1 ]],
            'customer'       => $u->stripe_customer_id ?: null,
            'customer_email' => $u->email,
            // 成功後は thanks で即時に pro へ昇格 → /admin/site に戻す
            'success_url' => route('site.thanks', [], false) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => route('site.paywall', [], false),
            'metadata'    => ['user_id' => $u->id],
        ]);

        return redirect($session->url);
    }

    public function thanks(Request $r)
    {
        $sessionId = $r->query('session_id');
        if (!$sessionId) {
            return redirect()->route('site.paywall')->with('error', '決済セッションが見つかりません');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        // セッションを取得して確定済みか検証
        $session = \Stripe\Checkout\Session::retrieve($sessionId);

        // 支払い完了判定（'complete' or payment_status 'paid'）
        if (($session->status === 'complete') || ($session->payment_status === 'paid')) {
            $u = $r->user();
            // 顧客IDを保存し、即時 pro 化
            $u->stripe_customer_id = $session->customer ?: $u->stripe_customer_id;
            $u->account_type = 'pro';
            $u->save();

            return redirect()->route('site.builder')->with('ok', 'ご購入ありがとうございます！');
        }

        // まだ未反映ならペイウォールに戻す or 「処理中」画面へ
        return redirect()->route('site.paywall')->with('error', '決済処理を確認できませんでした');
    }
}
