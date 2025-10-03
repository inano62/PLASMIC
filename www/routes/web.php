<?php


use App\Http\Controllers\BillingController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\PublicSiteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Stripe\StripeClient;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\{User,Site,Tenant,Page};
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;
require __DIR__ . '/auth.php';
// routes/web.php の上のほうに追加（他の /login ルートより前に）

Route::post('/signup-and-checkout', function (Request $r) {
    $v = $r->validate([
        'name'     => ['required','string','max:255'],
        'email'    => ['required','email','max:255'],
        'password' => ['required','string','min:8'],
        'price_id' => ['required','string'],
    ]);

    // 既存ならログイン、新規なら作成→ログイン
    $user = User::where('email', $v['email'])->first();
    if (! $user) {
        $user = User::create([
            'name'=>$v['name'],
            'email'=>$v['email'],
            'password'=>Hash::make($v['password']),
        ]);
        $tenant = Tenant::create([
            'slug'         => Str::slug($user->name).'-'.$user->id,
            'display_name' => $user->name.' 事務所',
            'region'       => '', // 初期値
            'type'         => '', // 初期値
        ]);

        $site = Site::create([
            'tenant_id' => $tenant->id,
            'slug'      => $tenant->slug,
            'title'     => $tenant->display_name,
            'meta'      => [],
        ]);

        Page::create([
            'site_id' => $site->id,
            'title'   => 'トップページ',
            'path'    => '/',
            'sort'    => 1,
            'blocks'  => json_encode([]), // ダミーブロックは後で追加
        ]);

    }
    if (! Auth::attempt(['email'=>$v['email'],'password'=>$v['password']], true)) {
        return response()->json(['message'=>'パスワードが違います'], 422);
    }
    $r->session()->regenerate();

    // Stripe Checkout
    $stripe = new StripeClient(config('services.stripe.secret') ?? env('STRIPE_SECRET'));

    if (! $user->stripe_customer_id) {
        $cust = $stripe->customers->create(['email'=>$user->email,'name'=>$user->name]);
        $user->stripe_customer_id = $cust->id;
        $user->save();
    }
    $session = $stripe->checkout->sessions->create([
        'mode'       => 'subscription',
        'customer'   => $user->stripe_customer_id,
        'line_items' => [[ 'price' => $v['price_id'], 'quantity' => 1 ]],
        'success_url'=> env('APP_FRONTEND_URL').'/billing/success?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => env('APP_FRONTEND_URL').'/billing/cancel',
    ]);

    return response()->json(['url'=>$session->url]);
});
Route::get('/api/user', function (Request $r) {
    if (! $r->user()) {
        return response()->json(['message'=>'Unauthenticated'], 401);
    }
    return $r->user();
});

// web.php
Route::get('/{path}', function () {
    return response()->file(public_path('dist/index.html'));
})->where('path', '^(?!api)(?!sanctum)(?!login)(?!logout)(?!register)(?!up)(?!dist)(?!assets).*$');

Route::middleware('auth')->group(function () {
    Route::get('/admin/site',           [SiteController::class, 'builder'])->name('site.builder')->middleware('site.entitled');
    Route::get('/admin/site/pay',       [SiteController::class, 'paywall'])->name('site.paywall');
    Route::post('/admin/site/checkout', [SiteController::class, 'checkout'])->name('site.checkout');
    Route::get('/admin/site/thanks',    [SiteController::class, 'thanks'])->name('site.thanks');
});
// 1) 新規登録
Route::post('/register', function (Request $r) {
    $v = $r->validate([
        'name'     => ['required','string','max:255'],
        'email'    => ['required','email','max:255','unique:users,email'],
        'password' => ['required','string','min:8'],
    ]);
    $user = User::create([
        'name' => $v['name'],
        'email'=> $v['email'],
        'password' => Hash::make($v['password']),
    ]);
    Auth::login($user, true); // ログイン状態にする
    $r->session()->regenerate();

    return response()->json(['ok'=>true, 'user'=>$user], 201);
});

//Route::post('/logout', function (Request $r) {
//    Auth::guard('web')->logout();
//    $r->session()->invalidate();
//    $r->session()->regenerateToken();
//    return response()->noContent();
//});
Route::get('/thanks', [BillingController::class, 'thanks'])->name('billing.thanks');
// デバッグ
//Route::get('/me', fn(Request $r) => response()->json($r->user()));

