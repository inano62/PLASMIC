<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    PublicSiteApiController, SiteBuilderController, PublishController,
    PublicController, TimeslotController, AppointmentController,
    StripeWebhookController, TokenController, ReservationController,
    StripeController, ClientController,MediaController
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
Route::prefix('admin')->group(function () {
    Route::get   ('/sites/{id}',            [SiteBuilderController::class,'show']);
    Route::put   ('/sites/{id}',            [SiteBuilderController::class,'update']);
    Route::post  ('/sites/{id}/pages',      [SiteBuilderController::class,'addPage']);
    Route::post  ('/pages/{pageId}/blocks', [SiteBuilderController::class,'addBlock']);
    Route::post  ('/pages/{pageId}/reorder',[SiteBuilderController::class,'reorder']);
    Route::put   ('/blocks/{id}',           [SiteBuilderController::class,'updateBlock']);
    Route::delete('/blocks/{id}',           [SiteBuilderController::class,'deleteBlock']);
    Route::post  ('/sites/{id}/publish',    [SiteBuilderController::class,'publish']);
});

//Route::post('/media', [MediaController::class,'store']);
Route::post('/media', function (Request $r) {
    $r->validate([
        'file' => ['required','file','mimes:jpg,jpeg,png,webp,gif','max:10240'], // 10MBまで
    ]);

    $path = $r->file('file')->store('uploads', 'public');
    return [
        'url'  => asset('storage/'.$path),
        'path' => $path,
    ];
});
// フロントの赤ログ止める用スタブ（必要なら残す）
Route::get('/appointments/nearby', [AppointmentController::class, 'nearby']);
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
