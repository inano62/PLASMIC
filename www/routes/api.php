<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{PublicSiteApiController,
    PublicSiteController,
    SiteBuilderController,
    PublishController,
    PublicController,
    TimeslotController,
    AppointmentController,
    StripeWebhookController,
    TokenController,
    ReservationController,
    StripeController,
    ClientController,
    MediaController};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Firebase\JWT\JWT;   // ← 追加
use Firebase\JWT\Key;

Route::get('/ping', fn() => ['ok'=>true, 'time'=>now()->toIso8601String()]);

// ───── 公開サイト用 JSON ─────
Route::prefix('public')->group(function () {
    Route::get('/sites/{slug}',       [PublicSiteApiController::class, 'site']);
    Route::get('/sites/{slug}/page',  [PublicSiteApiController::class, 'page']);
    Route::get('/tenants',            [PublicController::class, 'tenants']);
    Route::get('/tenants/resolve',    [PublicController::class, 'resolveTenant']);
    Route::get('/tenants/{tenant}/pros',  [PublicController::class, 'pros']);
    Route::get('/tenants/{tenant}/slots', [TimeslotController::class, 'listOpen']);
    Route::get('/sites/by-slug/{slug}', [PublicSiteController::class, 'showBySlug']);
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
    Route::post('/appointments', [AppointmentController::class, 'storeForTenant']);
    Route::post('/upload', function (Request $req) {
        $req->validate([
            'file' => ['required','image','max:5120'], // 5MB
        ]);
        $path = $req->file('file')->storeAs(
            'public/site',
            Str::uuid().'.'.$req->file('file')->extension()
        );
        // /storage から配れるように（php artisan storage:link 済み前提）
        return ['url' => Storage::url($path)];
    })->middleware('auth');
});

Route::post('/media', [MediaController::class,'upload']);
Route::get('/media/{id}', [MediaController::class, 'show']);

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
Route::match(['GET','POST'], '/video/token', function (Request $r) {
    $room   = $r->input('room') ?: $r->query('room');
    $ticket = $r->input('ticket') ?: $r->query('ticket');
    $aid    = $r->input('aid') ?: $r->query('aid');

    // ticket → room_name
    if (!$room && $ticket) {
        try {
            $decoded = JWT::decode($ticket, new Key(env('TICKET_SECRET','changeme'), 'HS256'));
            $room = $decoded->sub ?? null;
        } catch (\Throwable $e) {}
    }
    // aid → appointments.room_name
    if (!$room && $aid) {
        $room = optional(\App\Models\Appointment::find($aid))->room_name;
    }
    abort_unless($room, 404, 'room not found');

    $apiKey    = env('LIVEKIT_API_KEY','devkey');
    $apiSecret = env('LIVEKIT_API_SECRET','devsecret');
    $wsUrl     = env('LIVEKIT_WS_URL', env('LIVEKIT_URL','ws://localhost:7880'));

    $identity = (string)($r->input('identity') ?: Str::uuid());
    $claims = [
        'jti'   => (string) Str::uuid(),
        'iss'   => $apiKey,
        'sub'   => $identity,
        'nbf'   => time()-10,
        'exp'   => time()+3600,
        'video' => ['roomJoin'=>true, 'room'=>$room, 'canPublish'=>true, 'canSubscribe'=>true, 'canPublishData'=>true],
    ];
    $jwt = JWT::encode($claims, $apiSecret, 'HS256');

    // フロントの揺れに両対応
    return response()->json([
        'url'         => $wsUrl,
        'wsUrl'       => $wsUrl,
        'token'       => $jwt,
        'accessToken' => $jwt,
        'room'        => $room,
        'identity'    => $identity,
    ]);
});

// ローカル互換：/api/dev/token を /api/video/token に寄せる
if (app()->environment('local')) {
    Route::match(['GET','POST'], '/dev/token', fn(Request $r) => app()->handle(Request::create('/api/video/token', $r->method(), $r->all())));
}
Route::post('/dev/token', function (Request $r) {
    $room     = $r->input('room')      ?? 'room_'.Str::lower(Str::random(6));
    $identity = $r->input('identity')  ?? 'guest_'.Str::lower(Str::random(6));
    $name     = $r->input('name', $identity);

    $apiKey    = env('LIVEKIT_API_KEY');
    $apiSecret = env('LIVEKIT_API_SECRET');
    if (!$apiKey || !$apiSecret) {
        return response()->json(['message' => 'LIVEKIT_API_KEY / LIVEKIT_API_SECRET が未設定'], 500);
    }

    // LiveKit の video grant
    $videoGrant = [
        'room'              => $room,
        'roomJoin'          => true,
        'canPublish'        => true,
        'canSubscribe'      => true,
        'canPublishData'    => true,
        'canUpdateOwnMetadata' => true,
    ];

    $now = time();
    $payload = [
        'iss'   => $apiKey,
        'sub'   => $identity,
        'nbf'   => $now - 1,
        'iat'   => $now,
        'exp'   => $now + 3600,
        'name'  => $name,
        'video' => $videoGrant,
        'metadata' => json_encode(['name' => $name]),
    ];

    return ['token' => JWT::encode($payload, $apiSecret, 'HS256')];
});
