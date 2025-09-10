<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    PublicSiteApiController, SiteBuilderController, PublishController,
    PublicController, TimeslotController, AppointmentController,
    StripeWebhookController, TokenController, ReservationController,
    StripeController, ClientController
};

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
    Route::get   ('/sites/{site}',         [SiteBuilderController::class, 'showSite']);
    Route::put   ('/sites/{site}',         [SiteBuilderController::class, 'updateSite']);
    Route::post  ('/sites/{site}/pages',   [SiteBuilderController::class, 'createPage']);
    Route::put   ('/pages/{page}',         [SiteBuilderController::class, 'updatePage']);
    Route::post  ('/pages/{page}/blocks',  [SiteBuilderController::class, 'createBlock']);
    Route::post  ('/pages/{page}/reorder', [SiteBuilderController::class, 'reorderBlocks']);
    Route::delete('/blocks/{block}',       [SiteBuilderController::class, 'destroyBlock']);
    Route::post  ('/sites/{site}/publish', [PublishController::class,     'publishSite']);
    Route::get('/ping', fn() => response()->json(['ok'=>true]));
});
    Route::get('/_debug/sites/{site}', [\App\Http\Controllers\SiteBuilderController::class, 'showSite']);

// ───── 面談/決済/etc ─────
Route::post('/clients/upsert',        [ClientController::class,     'upsert'])->name('clients.upsert');
Route::post('/reservations',          [ReservationController::class,'create']);
Route::post('/pay/checkout/{id}',     [StripeController::class,     'createCheckout']);
Route::post('/stripe/webhook',        [StripeWebhookController::class,'handle']); // ← 1本に統一
Route::post('/appointments',          [AppointmentController::class,'store']);
Route::get ('/appointments/{id}',     [AppointmentController::class,'show']);
Route::post('/appointments/{id}/ticket',[AppointmentController::class,'issueTicket']);
Route::get ('/appointments/upcoming', [AppointmentController::class,'upcomi']);

//// ───── 管理画面 ─────
//Route::prefix('admin')->group(function () {
//    Route::get ('/sites/{site}', [\App\Http\Controllers\SiteBuilderController::class, 'showSite']);
//    Route::put ('/sites/{site}', [\App\Http\Controllers\SiteBuilderController::class, 'updateSite']);
//    Route::post ('/sites/{site}/pages', [\App\Http\Controllers\SiteBuilderController::class, 'createPage']);
//    Route::post ('/sites/{site}/publish', [\App\Http\Controllers\SiteBuilderController::class, 'publishSite']);
//});
