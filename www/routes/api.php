<?php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    BillingController,
    PublicApiController,
    PublicSiteApiController,
    PublicSiteController,
    SettingsController,
    SiteBuilderController,
    PublicController,
    TimeslotController,
    AppointmentController,
    ReservationController,
    StripeController,
    ClientController,
    MediaController,
    CallLogController
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Firebase\JWT\JWT;   // ← 追加
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Log;
use App\Models\{User,CallLog,CallMessage};
use Laravel\Sanctum\PersonalAccessToken;

Route::get('/__dbcheck', function () {
    try {
        $path = config('database.connections.sqlite.database');

        return response()->json([
            'driver'       => config('database.default'),
            'sqlite_path'  => $path,
            'realpath'     => $path ? (file_exists($path) ? realpath($path) : null) : null,
            'exists'       => $path ? file_exists($path) : null,
            'users_count'  => DB::table('users')->count(),   // ここで DB が死んでたら catch に飛ぶ
            'admin'        => DB::table('users')->where('email','admin@example.com')->first(),
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'class' => get_class($e),
            'code'  => $e->getCode(),
        ], 500);
    }
});
Route::post('/auth/token', function (Request $r) {
    $cred = $r->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);
    $user = User::where('email', $cred['email'])->first();
    if (! $user || ! Hash::check($cred['password'], $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }
    $token = $user->createToken('admin')->plainTextToken; // ← Sanctum PAT
    return response()->json([
        'token' => $token,
        'user'  => $user->only('id','name','email'),
    ]);
});
Route::get('/whoami', function (Request $r) {
    // 1) 通常の guard（セッションなど）で取得
    $user = $r->user();

    // 2) 取れない時は Bearer トークンを手動解決（Sanctum）
    if (!$user && $token = $r->bearerToken()) {
        $pat = PersonalAccessToken::findToken($token);
        if ($pat) $user = $pat->tokenable; // ← User モデル
    }

    return response()->json([
        'user' => $user?->only('id','name','email'),
    ])->header('Cache-Control', 'no-store'); // ブラウザキャッシュ防止
});
Route::get('/ping', fn() => ['ok'=>true, 'time'=>now()->toIso8601String()]);

Route::get('/billing/session/{sid}', [BillingController::class, 'session']);
Route::post('/stripe/webhook', [BillingController::class, 'webhook']);
// ───── 公開サイト用 JSON ─────
Route::prefix('public')->group(function () {
    Route::get('/settings', [PublicApiController::class, 'settings']);
    Route::get('/tenants/list', [PublicSiteApiController::class, 'tenantsList']);
    Route::get('/sites/{slug}/page',  [PublicSiteApiController::class, 'page']);
    Route::get('/sites/{slug}',       [PublicSiteApiController::class, 'site']);
    Route::get('/tenants',            [PublicController::class, 'tenants']);
    Route::get('/tenants/resolve',    [PublicController::class, 'resolve']);
    Route::get('/tenants/{tenant}/pros',  [PublicController::class, 'pros']);
    Route::get('/tenants/{tenant}/slots', [AppointmentController::class,'publicSlots']);
    Route::get('/tenants/{tenant}/upcoming', [AppointmentController::class,'upcomingForTenant']);
    Route::get('/tenants/resolve', function (Request $r) {
        $key  = $r->query('key');
        $slug = $r->query('slug');
        abort_if(!$key && !$slug, 400, 'key or slug is required');

        $q = \App\Models\Tenant::query();
        if ($slug && \Illuminate\Support\Facades\Schema::hasColumn('tenants','slug')) {
            $q->where('slug', $slug);
        }
        if ($key) {
            $q->orWhere('key', $key);
        }

        $t = $q->firstOrFail();
        return response()->json([
            'id'           => (int)$t->id,
            'display_name' => $t->display_name ?? $t->name,
        ]);
    });
});
Route::get('/calls/debug/{room}', function ($room) {
    $row = \App\Models\CallLog::where('room_name', $room)
        ->orderByDesc('id')
        ->first(['id','room_name','started_at','ended_at','duration_sec','meta']);

    return response()->json($row); // ← これで確実にJSONが出る
});


Route::get('/calls/stream', function () {
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    while (true) {
        $rows = \App\Models\CallLog::orderByDesc('id')->limit(10)->get([
            'id','room_name','started_at','ended_at','duration_sec'
        ]);
        echo "event: call_logs\n";
        echo "data: ".json_encode($rows)."\n\n";
        @ob_flush(); flush();
        sleep(1);
    }
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
//Route::middleware('auth:sanctum')->get('/user', function (Request $r) {
//    return $r->user();
//});
Route::post('/media', [MediaController::class,'upload']);
Route::get('/media/{id}', [MediaController::class, 'show']);

// フロントの赤ログ止める用スタブ（必要なら残す）
Route::prefix('appointments')->group(function () {
    Route::get('nearby', [AppointmentController::class, 'nearby']); // 既存
    Route::get('{id}',   [AppointmentController::class, 'show'])
        ->whereNumber('id'); // ← これ大事
});
Route::get('/billing/session/{sid}', [BillingController::class, 'session']);
Route::middleware('auth:sanctum')->post('/billing/checkout', [BillingController::class, 'checkout']);
Route::middleware('auth:sanctum')->post('/billing/portal', function () {
    $stripe = new \Stripe\StripeClient(config('services.stripe.secret') ?? env('STRIPE_SECRET'));
    $url = $stripe->billingPortal->sessions->create([
        'customer'   => auth()->user()->stripe_customer_id,
        'return_url' => env('APP_URL').'/admin/account',
    ])->url;
    return ['url' => $url];
});
// ───── 面談/決済/etc ─────
Route::post('/clients/upsert',        [ClientController::class,     'upsert'])->name('clients.upsert');
Route::post('/reservations',          [ReservationController::class,'create']);
Route::post('/pay/checkout/{id}',     [StripeController::class,     'createCheckout']);
Route::post('/appointments',          [AppointmentController::class,'store']);
Route::get ('/appointments/{id}',     [AppointmentController::class,'show']);
Route::post('/appointments/{id}/ticket',[AppointmentController::class,'issueTicket']);
Route::get ('/appointments/upcoming', [AppointmentController::class,'upcomi']);
Route::post('/inquiries', [PublicSiteController::class, 'storeInquiry']);
Route::get('/inquiries', [PublicSiteController::class, 'index']);          // 管理画面用: 新着一覧
Route::get('/inquiries/{id}', [PublicSiteController::class, 'show']);      // 詳細
Route::patch('/inquiries/{id}', [PublicSiteController::class, 'update']);
Route::get('/settings', [SettingsController::class, 'show']);

Route::post('/auth/token', function (Request $r) {
    $cred = $r->validate([
        'email'    => 'required|email',
        'password' => 'required',
    ]);

    $user = User::where('email', $cred['email'])->first();

    if (!$user || !Hash::check($cred['password'], $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    $token = $user->createToken('admin')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user'  => [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
        ],
    ]);
});
Route::post('/signup-and-checkout', [StripeController::class, 'signupAndCheckout']);
Route::post('/calls/event', [CallLogController::class, 'store']);
Route::match(['GET','POST'], '/video/token', function (Request $r) {

    $room   = $r->input('room') ?: $r->query('room');
    $ticket = $r->input('ticket') ?: $r->query('ticket');
    $aid    = $r->input('aid') ?: $r->query('aid');

    $appointment = \App\Models\Appointment::find($aid);
    $hostId  = $appointment?->lawyer_user_id ?? auth()->id();
    $guestId = $appointment?->client_user_id;
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

    $log = CallLog::firstOrCreate(
        ['room_name'=>$room, 'started_at'=>now()->subMinutes(10)], // 実運用は厳密キーを工夫
        [
            'appointment_id'=>$aid,
            'host_user_id'=>$hostId,
            'guest_user_id'=>$guestId,
            'started_at'=>now()
        ]
    );
    $log->update([
        'ended_at' => now(),
        'duration_sec' => $log->started_at
            ? $log->started_at->diffInSeconds(now(), true)
            : null,
    ]);
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
Route::post('/video/call/end', function (Request $r) {
    $room = $r->input('room');
    abort_unless($room, 400, 'room required');

    $log = CallLog::where('room_name', $room)->latest('id')->first();
    if (!$log) return response()->json(['ok'=>true]); // ないなら黙ってOK

    $ended = now();
    $log->ended_at    = $ended;
    $log->duration_sec= $log->started_at ? $ended->diffInSeconds($log->started_at) : null;
    $log->save();

    return ['ok'=>true];
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
