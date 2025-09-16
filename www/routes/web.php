<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\SiteController;
//use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicSiteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Stripe\StripeClient;
require __DIR__ . '/auth.php';
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
    Auth::login($user, true);
    $r->session()->regenerate();
    return response()->json(['ok'=>true], 201);
});
// 2) ログイン
Route::post('/login', function (Request $r) {
    $cred = $r->validate([
        'email'    => ['required','email'],
        'password' => ['required','string'],
    ]);
    if (! Auth::attempt($cred, true)) {
        return response()->json(['message' => 'Invalid credentials'], 422);
    }
    $r->session()->regenerate();
    return response()->json(['ok'=>true]);
});
Route::get('/thanks', [BillingController::class, 'thanks'])->name('billing.thanks');


// 3) ログアウト
Route::post('/logout', function (Request $r) {
    Auth::logout();
    $r->session()->invalidate();
    $r->session()->regenerateToken();
    return response()->json(['ok'=>true]);
});
// デバッグ
Route::get('/me', fn(Request $r) => response()->json($r->user()));
// サイト情報JSON
Route::get('/api/public/sites/{slug}', [PublicSiteController::class, 'site']);
Route::get('/api/public/sites/{slug}/page', [PublicSiteController::class, 'page']);
