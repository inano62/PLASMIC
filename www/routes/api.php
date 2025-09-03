<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\TokenController;
use App\Http\Controllers\StripeController;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\TimeslotController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\AppointmentController;
use Illuminate\Http\Request;
use Livekit\AccessToken;
use Agence104\LiveKit\VideoGrant;
use Agence104\LiveKit\AccessTokenOptions;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\Reservation;

Route::post('/rooms/issue', [RoomController::class, 'issue']);
Route::post('/reservations', [ReservationController::class, 'create']);
Route::get('/availability', [ReservationController::class, 'availability']);
Route::post('/pay/checkout', [StripeController::class, 'createCheckout']);
Route::get('/pay/confirm',  [StripeController::class, 'confirm']);
Route::post('/pay/checkout/{id}', [StripeController::class, 'createCheckout']); // returns {url}
Route::post('/stripe/webhook', [StripeController::class, 'webhook']);
Route::post('/reservations', [ReservationController::class, 'create']);       // 予約作成（未払い）
Route::get('/reservations/{id}', [ReservationController::class, 'show']);     // 支払い後にURLを取得
Route::post('/pay/checkout/{id}', [StripeController::class, 'createCheckout']); // Stripeへ飛ばす
Route::post('/webhooks/stripe', [StripeController::class, 'webhook']);        // 決済完了でpaid化
Route::post('/exchange-token', [TokenController::class, 'exchange']);         // Join時のトークン発行（本番用）
Route::post('/exchange-token', [TokenController::class, 'exchange']); // role+code → LiveKit JWT
Route::post('/subscriptions/start', [SubscriptionController::class, 'start']);
Route::get('/settings', fn() => DB::table('settings')->pluck('value', 'key'));
Route::put('/settings', function (Request $r) {
    foreach ($r->all() as $k=>$v) {
        DB::table('settings')->updateOrInsert(['key'=>$k], ['value'=>is_string($v)?$v:json_encode($v)]);
    }
    return response()->json(['ok'=>true]);
});
Route::get('/settingIndex',  [SettingsController::class, 'index']);
Route::post('/settings', [SettingsController::class, 'update']); // とりあえずPOSTで更新
Route::post('/upload',   [SettingsController::class, 'upload']); // 画像アップ

Route::post('/subscriptions/start', [SubscriptionController::class, 'start']); // プロの月額Checkout
// 公開：先生一覧 / 先生の空き枠
Route::get('/public/tenants', [PublicController::class, 'tenants']);
Route::get('/public/tenants/{id}/slots', [TimeslotController::class, 'listOpen']);

// 予約 → 支払い用PI作成（Connect 10%）→ client_secret返す
Route::post('/reservations', [ReservationController::class, 'createForSlot']);

// Proサブスク（前に作ったやつ）
Route::post('/subscriptions/start', [SubscriptionController::class, 'start']);

Route::post('/stripe/webhook', [\App\Http\Controllers\StripeWebhookController::class, 'handle']);

/** 近接予約（士業ダッシュボード用） 認証が無ければクエリで仮指定 */
Route::get('/appointments/nearby', function(Request $r){
    $lawyerId = $r->query('lawyer_id', 1); // ←まずは仮で1
    $from = now()->subMinutes(5); $to = now()->addMinutes(60);
    $rows = DB::table('appointments')
        ->where('lawyer_id',$lawyerId)
        ->whereBetween('starts_at', [$from,$to])
        ->where('status','booked')
        ->orderBy('starts_at')
        ->get(['id','client_name','starts_at','room']);
    return response()->json($rows);
});

Route::get('/timeslots', function (Request $r) {
    $lawyerId = (int) $r->query('lawyer_id', 1);
    $days     = min((int) $r->query('days', 7), 14);   // 最大2週間
    $tz       = 'Asia/Tokyo';
    $now      = Carbon::now($tz);

    // 営業時間テンプレ（1=Mon, ... 5=Fri）
    $hours = [
        1 => ['09:00', '17:30'],
        2 => ['09:00', '17:30'],
        3 => ['09:00', '17:30'],
        4 => ['09:00', '17:30'],
        5 => ['09:00', '17:30'],
    ];
    $slot = CarbonInterval::minutes(30);
    $bufferMin = 0; // 必要なら前後バッファを入れる

    $result = [];
    for ($i=0; $i<$days; $i++) {
        $day = $now->copy()->startOfDay()->addDays($i);
        $dow = (int)$day->dayOfWeekIso; // Mon=1 ... Sun=7
        if (!isset($hours[$dow])) continue;

        [$startStr, $endStr] = $hours[$dow];
        $start = $day->copy()->setTimeFromTimeString($startStr);
        $end   = $day->copy()->setTimeFromTimeString($endStr);

        $slots = [];
        for ($t=$start->copy(); $t < $end; $t->add($slot)) {
            // 過去を除外
            if ($t->lt($now)) continue;

            $tEnd = $t->copy()->add($slot);
            // 予約衝突チェック（同一士業、30分被り）
            $conflict = DB::table('appointments')
                ->where('lawyer_id', $lawyerId)
                ->where('status', 'booked')
                ->whereBetween('starts_at', [$t->copy()->subMinutes($bufferMin), $tEnd->copy()->addMinutes($bufferMin)])
                ->exists();

            if (!$conflict) {
                $slots[] = $t->toIso8601String();
            }
        }
        if ($slots) {
            $result[] = ['date' => $day->toDateString(), 'slots' => $slots];
        }
    }
    return response()->json($result);
});
/**  routes/api.php に aid→room を返すAPIがあるか確認。無ければ追加。 */

//Route::post('/appointments', [AppointmentController::class, 'store']);
Route::get('/appointments/window', function (Request $r) {
    $lawyerId  = (int) $r->query('lawyer_id', 1);
    $beforeMin = min(max((int) $r->query('before_min', 60), 0), 10080);     // 0〜7日
    $afterMin  = min(max((int) $r->query('after_min', 14*24*60), 30), 90*24*60);

    $from = now()->subMinutes($beforeMin);
    $to   = now()->addMinutes($afterMin);

    $rows = DB::table('appointments')
        ->where('lawyer_id', $lawyerId)
        ->whereBetween('starts_at', [$from, $to])
        ->orderBy('starts_at')
        ->get(['id','client_name','starts_at','room','status']);

    return response()->json($rows);
});
Route::get('/appointments/upcoming', function (Request $r) {
    $lawyerId = (int) $r->query('lawyer_id', 1);
    $days     = min((int) $r->query('days', 14), 60);
    $from = now()->subMinutes(5);          // 5分前〜
    $to   = now()->addDays($days);         // 直近N日
    $rows = DB::table('appointments')
        ->where('lawyer_id', $lawyerId)
        ->where('status', 'booked')
        ->whereBetween('starts_at', [$from, $to])
        ->orderBy('starts_at')
        ->get(['id','client_name','starts_at','room']);
    return response()->json($rows);
});
Route::get('/appointments/past', function (Request $r) {
    $lawyerId = (int) $r->query('lawyer_id', 1);
    $days  = min((int) $r->query('days', 90), 365);
    $page  = max((int) $r->query('page', 1), 1);
    $per   = min(max((int) $r->query('per', 50), 10), 200);

    $from = now()->subDays($days);
    $q = DB::table('appointments')
        ->where('lawyer_id', $lawyerId)
        ->where('starts_at', '<', now())
        ->where('starts_at', '>=', $from);

    $total = $q->count();
    $rows  = $q->orderByDesc('starts_at')
        ->offset(($page-1)*$per)->limit($per)
        ->get(['id','client_name','client_email','client_phone','starts_at','status']);

    return response()->json([
        'total'=>$total, 'page'=>$page, 'per'=>$per, 'rows'=>$rows
    ]);
});
Route::get('/my/appointments', function(Request $r){
    $vid = $r->query('visitor_id'); if(!$vid) return response()->json([]);
    $rows = DB::table('appointments')
        ->where('visitor_id', $vid)
        ->orderByDesc('starts_at')
        ->limit(1)
        ->get(['id','starts_at','status']);
    return response()->json($rows);
});
Route::post('/my/appointments/lookup', [AppointmentLookupController::class, 'lookup']);
Route::get('/appointments/{id}', function ($id) {
    $row = Reservation::find($id);
    if (!$row) {
        return response()->json(['message' => 'not found'], 404);
    }
    return response()->json([
        'id'        => $row->id,
        'room_name' => $row->room_name,
        'start_at'  => $row->starts_at,
        'status'    => $row->status,
    ]);
});
Route::get('/availability', [AvailabilityController::class, 'index']);
//Route::post('/dev/token', function (Request $r) {
//    $room     = $r->input('room', 'test-room');
//    $identity = $r->input('identity', 'user-'.bin2hex(random_bytes(3)));
//
//    $apiKey    = env('LIVEKIT_API_KEY', 'devkey');
//    $apiSecret = env('LIVEKIT_API_SECRET', 'devsecret');
//    $url       = env('LIVEKIT_URL', null); // 例: wss://your-livekit.example.com
//
//    // LiveKit の JWT（シンプルな video grant）
//    $payload = [
//        'iss'   => $apiKey,
//        'sub'   => $identity,
//        'iat'   => time(),
//        'exp'   => time() + 3600,
//        // v0/v1 互換。最近の SDK でも "video" トップレベル grant は有効
//        'video' => [
//            'roomJoin'     => true,
//            'room'         => $room,
//            'canPublish'   => true,
//            'canSubscribe' => true,
//        ],
//    ];
//
//    $jwt = JWT::encode($payload, $apiSecret, 'HS256');
//
//    return response()->json([
//        'token' => $jwt,
//        'url'   => $url, // 無ければフロントが dev 用 fallback を使う
//    ]);
//});
//Route::get('/wait/resolve', function (Request $r) {
//    $ticket = $r->query('ticket');
//    if (!$ticket) return response()->json(['message'=>'ticket required'], 400);
//    try {
//        $payload = JWT::decode($ticket, new Key(env('TICKET_SECRET','changeme'), 'HS256'));
//        $room = $payload->sub ?? null;
//        if (!$room) return response()->json(['message'=>'invalid ticket'], 404);
//        return response()->json(['room' => $room]);
//    } catch (\Throwable $e) {
//        return response()->json(['message'=>'invalid ticket','detail'=>$e->getMessage()], 400);
//    }
//});
Route::get('/wait/verify/{ticket}', function (string $ticket) {
    // ★暫定：JWT検証を飛ばして、予約IDを ticket に直接入れてる前提にする
    $res = \App\Models\Reservation::findOrFail((int) $ticket);
    return [
        'reservation_id' => $res->id,
        'room' => $res->room_name,
        'guest_name' => $res->guest_name,
        'starts_at' => $res->start_at,
    ];
});
Route::post('/wait/ticket', function (Request $r) {
    $resId = (int) $r->input('reservation_id');
    if (!$resId) {
        return response()->json(['message' => 'reservation_id required'], 400);
    }

    $res = Reservation::find($resId);
    if (!$res) {
        return response()->json(['message' => 'reservation not found'], 404);
    }

    // ★ ここで発行
    $jwt = JWT::encode(
        ['sub' => $res->id, 'exp' => time() + 900], // 15分有効など
        env('TICKET_SECRET'),
        'HS256'
    );

    return response()->json([
        'ticket'         => $jwt,
        'clientJoinPath' => '/wait?ticket=' . $jwt,   // 顧客用
        'hostJoinPath'   => '/host?aid=' . $res->id,  // 士業用（aid方式）
    ]);
});
Route::get('/wait/verify', function (Illuminate\Http\Request $r) {
    $ticket = $r->query('ticket');
    if (!$ticket) return response()->json(['message'=>'ticket required'], 400);
    try {
        $payload = \Firebase\JWT\JWT::decode($ticket, new \Firebase\JWT\Key(env('TICKET_SECRET'), 'HS256'));
    } catch (\Throwable $e) {
        return response()->json(['message'=>'invalid ticket','detail'=>$e->getMessage()], 400);
    }
    $res = \App\Models\Reservation::findOrFail($payload->sub);
    return response()->json([
        'reservation_id' => $res->id,
        'room'           => $res->room_name,
        'guest_name'     => $res->guest_name,
        'starts_at'      => $res->start_at,
    ]);
});

Route::get('/wait/verify', function (Request $r) {
    $ticket = $r->query('ticket');
    if (!$ticket) {
        return response()->json(['message'=>'ticket required'], 400);
    }
    try {
        $payload = JWT::decode($ticket, new Key(env('TICKET_SECRET'), 'HS256'));
    } catch (\Throwable $e) {
        return response()->json(['message'=>'invalid ticket', 'detail'=>$e->getMessage()], 400);
    }
    $res = Reservation::find($payload->sub);
    if (!$res) {
        return response()->json(['message' => 'not found'], 404);
    }
    return response()->json([
        'reservation_id' => $res->id,
        'room'           => $res->room_name,
        'guest_name'     => $res->guest_name,
        'starts_at'      => $res->start_at,
    ]);
});
Route::get('/appointments/resolve', [AppointmentController::class, 'resolve']);



// 1) LiveKit トークン（最小）
Route::post('/dev/token', function (Request $r) {
    $room = $r->input('room', 'test-room');
    $identity = $r->input('identity', 'user-' . bin2hex(random_bytes(3)));

    $apiKey = env('LIVEKIT_API_KEY', 'devkey');
    $apiSecret = env('LIVEKIT_API_SECRET', 'devsecret');
    $url = env('LIVEKIT_URL', null); // 例: wss://your-livekit.example.com

    $payload = [
        'iss' => $apiKey,
        'sub' => $identity,
        'iat' => time(),
        'exp' => time() + 3600,
        'video' => [
            'roomJoin' => true,
            'room' => $room,
            'canPublish' => true,
            'canSubscribe' => true,
        ],
    ];
    $jwt = JWT::encode($payload, $apiSecret, 'HS256');

    return response()->json(['token' => $jwt, 'url' => $url]);
});

// 2) ticket → room を解決（予約DBを使わず “room 名を sub に入れる” 方式）
Route::get('/wait/resolve', function (Request $r) {
    $ticket = $r->query('ticket');
    if (!$ticket) return response()->json(['message' => 'ticket required'], 400);
    try {
        $payload = JWT::decode($ticket, new Key(env('TICKET_SECRET', 'changeme'), 'HS256'));
        $room = $payload->sub ?? null;
        if (!$room) return response()->json(['message' => 'invalid ticket'], 404);
        return response()->json(['room' => $room]);
    } catch (\Throwable $e) {
        return response()->json(['message' => 'invalid ticket', 'detail' => $e->getMessage()], 400);
    }
});

// 3) デモ用：/api/appointments を “DB不要のスタブ” に差し替え
//   予約フォームが叩いてくるので 201 で部屋URLを返すだけ
Route::post('/appointments', function (Request $r) {
    $id = (string)Str::uuid();
    $room = 'room_' . Str::lower(Str::random(10));

    // ticket=room 方式で発行（sub に room を入れる）
    $ticket = JWT::encode(
        ['sub' => $room, 'exp' => time() + 1800],
        env('TICKET_SECRET', 'changeme'),
        'HS256'
    );

    return response()->json([
        'appointmentId' => $id,
        'clientJoinPath' => '/wait?room=' . $room,   // ゲストはこれで入れる
        'hostJoinPath' => '/host?room=' . $room,   // ホストも room で入れる
        'ticket' => $ticket,               // もし ticket で渡したいなら使う
    ], 201);
});
