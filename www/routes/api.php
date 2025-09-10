<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    PublicSiteApiController, SiteBuilderController, PublishController,
    PublicController, TimeslotController, AppointmentController,
    StripeWebhookController, TokenController, ReservationController,
    StripeController, ClientController
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

Route::get('/ping', fn() => ['ok'=>true, 'time'=>now()->toIso8601String()]);

// ───── 公開サイト用 JSON ─────
Route::prefix('public')->group(function () {
    Route::get('/sites/{slug}',       [PublicSiteApiController::class, 'site']);
    Route::get('/sites/{slug}/page',  [PublicSiteApiController::class, 'page']);
    Route::get('/tenants',            [PublicController::class, 'tenants']);
    Route::get('/tenants/resolve',    [PublicController::class, 'resolveTenant']);
    Route::get('/tenants/{tenant}/pros',  [PublicController::class, 'pros']);
    Route::get('/tenants/{tenant}/slots', [TimeslotController::class, 'listOpen']);
});

// ───── テナント配下（ID/slug どちらでもOKにしているならルートモデルに合わせて） ─────
Route::prefix('tenants/{tenant}')->group(function () {
    Route::get('/availability',       [TimeslotController::class, 'listOpenForTenant']);
    Route::get('/my/appointments',    [AppointmentController::class, 'myForTenant']); // or myByVisitor
    Route::post('/appointments',      [AppointmentController::class, 'storeForTenant']);
});

// ───── Builder 管理API（Sanctum） ─────
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get   ('/sites/{id}',           [SiteBuilderController::class, 'show']);
    Route::put   ('/sites/{id}',           [SiteBuilderController::class, 'update']);
    Route::post  ('/sites/{id}/pages',     [SiteBuilderController::class, 'addPage']);
    Route::post  ('/pages/{pageId}/blocks',[SiteBuilderController::class, 'addBlock']);
    Route::post  ('/pages/{pageId}/reorder',[SiteBuilderController::class, 'reorder']);
    Route::put   ('/blocks/{id}',          [SiteBuilderController::class, 'updateBlock']);
    Route::delete('/blocks/{id}',          [SiteBuilderController::class, 'deleteBlock']);
    Route::post  ('/sites/{id}/publish',   [SiteBuilderController::class, 'publish']);
});
    Route::get('/_debug/sites/{site}', [\App\Http\Controllers\SiteBuilderController::class, 'showSite']);
Route::middleware('auth:sanctum')->get('/whoami', fn() =>
response()->json(['user' => auth()->user()?->only('id','name','email')])
);
// ───── 面談/決済/etc ─────
Route::post('/clients/upsert',        [ClientController::class,     'upsert'])->name('clients.upsert');
Route::post('/reservations',          [ReservationController::class,'create']);
Route::post('/pay/checkout/{id}',     [StripeController::class,     'createCheckout']);
Route::post('/stripe/webhook',        [StripeWebhookController::class,'handle']); // ← 1本に統一
Route::post('/appointments',          [AppointmentController::class,'store']);
Route::get ('/appointments/{id}',     [AppointmentController::class,'show']);
Route::post('/appointments/{id}/ticket',[AppointmentController::class,'issueTicket']);
Route::get ('/appointments/upcoming', [AppointmentController::class,'upcomi']);


Route::post('/auth/token', function (Request $r) {
    $cred = $r->validate(['email'=>'required|email','password'=>'required']);
    if (!Auth::attempt($cred)) {
        return response()->json(['message'=>'Invalid credentials'], 401);
    }
    $user = $r->user();
    $token = $user->createToken('admin')->plainTextToken;
    return response()->json(['token'=>$token, 'user'=>['id'=>$user->id,'name'=>$user->name]]);
});
/**
 * ① 管理ログイン（Sanctum トークン発行）
 * React 側から email/password を送ると Bearer トークンを返します。
 * このトークンを Authorization: Bearer xxx で付けて以降の /admin API を叩く。
 */
Route::post('/auth/token', function (Request $r) {
    $cred = $r->validate(['email'=>'required|email', 'password'=>'required']);
    if (!Auth::attempt($cred)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }
    /** @var \App\Models\User $user */
    $user  = $r->user();
    $token = $user->createToken('admin')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user'  => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
    ]);
});

/**
 * ② 管理API（Sanctum で保護）
 * React の Builder.tsx が呼ぶパスに完全対応
 */
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    // サイト本体
    Route::get   ('/sites/{site}',        [SiteBuilderController::class, 'showSite']);   // 期待: { site, pages:[{..., blocks:[]}] }
    Route::put   ('/sites/{site}',        [SiteBuilderController::class, 'updateSite']); // body: {title, slug, meta}

    // ページ作成・並べ替え
    Route::post  ('/sites/{site}/pages',  [SiteBuilderController::class, 'createPage']); // body: {path, title}
    Route::post  ('/pages/{page}/reorder',[SiteBuilderController::class, 'reorderBlocks']); // body: {ids:[blockId...]}

    // ブロック CRUD
    Route::post  ('/pages/{page}/blocks', [SiteBuilderController::class, 'createBlock']); // body: {type}
    Route::put   ('/blocks/{block}',      [SiteBuilderController::class, 'updateBlock']); // body: {data, sort}
    Route::delete('/blocks/{block}',      [SiteBuilderController::class, 'destroyBlock']);

    // 公開
    Route::post  ('/sites/{site}/publish',[PublishController::class, 'publishSite']);

    // 必要なら追加
    // Route::get('/appointments/upcoming', [AppointmentController::class, 'upcoming']);
});
