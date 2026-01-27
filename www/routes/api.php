<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AppointmentController,
    AuthController,
    BillingController,
    CallLogController,
    ClientController,
    HealthCheckController,
    LivekitController,
    MediaController,
    PublicApiController,
    PublicController,
    PublicSiteApiController,
    PublicSiteController,
    ReservationController,
    SettingsController,
    SiteBuilderController,
    StripeController,
    TimeslotController
};

Route::get('/__dbcheck', [HealthCheckController::class, 'dbCheck']);
Route::get('/ping', [HealthCheckController::class, 'ping']);

Route::post('/auth/token', [AuthController::class, 'issueToken']);
Route::get('/whoami', [AuthController::class, 'whoami']);
Route::post('/login', [AuthController::class, 'apiLogin']);
Route::post('/logout', [AuthController::class, 'apiLogout'])->middleware('auth:sanctum');
Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');

Route::prefix('admin')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('/sites/me', [SiteBuilderController::class, 'show']);
        Route::put('/sites/{id}', [SiteBuilderController::class, 'update']);
        Route::post('/sites/{id}/pages', [SiteBuilderController::class, 'addPage']);
        Route::post('/pages/{pageId}/blocks', [SiteBuilderController::class, 'addBlock']);
        Route::post('/pages/{pageId}/reorder', [SiteBuilderController::class, 'reorder']);
        Route::put('/blocks/{id}', [SiteBuilderController::class, 'updateBlock']);
        Route::delete('/blocks/{id}', [SiteBuilderController::class, 'deleteBlock']);
        Route::post('/sites/{id}/publish', [SiteBuilderController::class, 'publish']);
        Route::post('/media', [MediaController::class, 'upload']);
        Route::get('/media/{id}', [MediaController::class, 'show'])->name('admin.media.show');
    });

Route::get('/billing/session/{sid}', [BillingController::class, 'session']);
Route::post('/stripe/webhook', [BillingController::class, 'webhook']);
Route::middleware('auth:sanctum')->post('/billing/checkout', [BillingController::class, 'checkout']);
Route::middleware('auth:sanctum')->post('/billing/portal', [BillingController::class, 'portal']);

Route::prefix('public')->group(function () {
    Route::post('/tenants/{tenant}/appointments', [AppointmentController::class, 'storeForTenant']);
    Route::get('/settings', [PublicApiController::class, 'settings']);
    Route::get('/tenants/list', [PublicSiteApiController::class, 'tenantsList']);
    Route::get('/sites/{slug}/page', [PublicSiteApiController::class, 'page']);
    Route::get('/sites/{slug}', [PublicSiteApiController::class, 'site']);
    Route::get('/tenants', [PublicController::class, 'tenants']);
    Route::get('/tenants/resolve', [PublicController::class, 'resolve']);
    Route::get('/tenants/{tenant}/pros', [PublicController::class, 'pros']);
    Route::get('/tenants/{tenant}/slots', [AppointmentController::class, 'publicSlots']);
    Route::get('/tenants/{tenant}/upcoming', [AppointmentController::class, 'upcomingForTenant']);
});

Route::get('/calls/debug/{room}', [CallLogController::class, 'debug']);
Route::get('/calls/stream', [CallLogController::class, 'stream']);

Route::prefix('tenants/{tenant}')->group(function () {
    Route::get('/availability', [TimeslotController::class, 'listOpenForTenant']);
    Route::get('/my/appointments', [AppointmentController::class, 'myForTenant']);
    Route::post('/appointments', [AppointmentController::class, 'storeForTenant']);
});

Route::prefix('appointments')->group(function () {
    Route::get('nearby', [AppointmentController::class, 'nearby']);
    Route::get('{id}', [AppointmentController::class, 'show'])->whereNumber('id');
});

Route::post('/clients/upsert', [ClientController::class, 'upsert'])->name('clients.upsert');
Route::post('/reservations', [ReservationController::class, 'create']);
Route::post('/pay/checkout/{id}', [StripeController::class, 'createCheckout']);
Route::post('/appointments', [AppointmentController::class, 'store']);
Route::get('/appointments/{id}', [AppointmentController::class, 'show']);
Route::post('/appointments/{id}/ticket', [AppointmentController::class, 'issueTicket']);
Route::get('/appointments/upcoming', [AppointmentController::class, 'upcomi']);
Route::post('/inquiries', [PublicSiteController::class, 'storeInquiry']);
Route::get('/inquiries', [PublicSiteController::class, 'index']);
Route::get('/inquiries/{id}', [PublicSiteController::class, 'show']);
Route::patch('/inquiries/{id}', [PublicSiteController::class, 'update']);
Route::get('/settings', [SettingsController::class, 'show']);

Route::post('/signup-and-checkout', [StripeController::class, 'signupAndCheckout']);
Route::post('/calls/event', [CallLogController::class, 'store']);

Route::match(['GET', 'POST'], '/video/token', [LivekitController::class, 'issueToken']);
Route::post('/video/call/end', [CallLogController::class, 'endCall']);

if (app()->environment('local')) {
    Route::get('/dev/token', [LivekitController::class, 'issueToken']);
}

Route::post('/dev/token', [LivekitController::class, 'devToken']);
