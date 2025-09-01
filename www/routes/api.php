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
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Agence104\LiveKit\AccessToken;
use Agence104\LiveKit\VideoGrant;
use Agence104\LiveKit\AccessTokenOptions;
use Carbon\Carbon;
use Carbon\CarbonInterval;

Route::post('/rooms/issue', [RoomController::class, 'issue']);
Route::post('/reservations', [ReservationController::class, 'create']);
Route::get('/availability', [ReservationController::class, 'availability']);
Route::post('/pay/checkout', [StripeController::class, 'createCheckout']);
Route::get('/pay/confirm',  [StripeController::class, 'confirm']);
//Route::post('/dev/token', [TokenController::class, 'devToken']);
//Route::post('/dev/token', function (Request $r) {
//    $room = $r->input('room');
//    $identity = $r->input('identity') ?? ('host-' . Str::uuid());
//    if (!$room) return response()->json(['error'=>'room required'], 400);
//
//    $at = new AccessToken(env('LIVEKIT_API_KEY'), env('LIVEKIT_API_SECRET'), ['identity'=>$identity, 'ttl'=>3600]);
//    $grant = new VideoGrant(['roomJoin'=>true, 'room'=>$room, 'canPublish'=>true, 'canSubscribe'=>true]);
//    $at->addGrant($grant);
//    $jwt = $at->toJwt();
//
//    return response()->json(['token'=>$jwt, 'url'=>env('LIVEKIT_URL')]); // wss://...
//});
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
/** 予約作成（お客さんが発行） */
//Route::post('/appointments', function(Request $r){
//    $data = $r->validate([
//        'lawyer_id'   => 'required|integer',
//        'client_name' => 'required|string',
//        'client_email'=> 'nullable|email',
//        'client_phone'=> 'nullable|string',
//        'starts_at'   => 'required|date', // ISO8601
//    ]);
//    $room = 'r_'.Str::uuid();
//    $id = DB::table('appointments')->insertGetId([
//        ...$data, 'room'=>$room, 'status'=>'booked',
//        'created_at'=>now(), 'updated_at'=>now(),
//    ]);
//    return response()->json([
//        'appointmentId'=>$id,
//        'room'=>$room,
//        'clientJoinUrl'=> url("/wait?aid=$id"),
//        'hostJoinUrl'  => url("/host?aid=$id"),
//    ]);
//});

/** aid→room を返す（顧客/士業 共通） */
//Route::get('/appointments/{id}', function($id){
//    $row = DB::table('appointments')->where('id',$id)->first(['room','starts_at','status']);
//    abort_if(!$row, 404);
//    return response()->json($row);
//});

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

Route::post('/appointments', function(Request $r){
    $data = $r->validate([
        'lawyer_id'   => 'required|integer',
        'client_name' => 'required|string',
        'client_email'=> 'nullable|email',
        'client_phone'=> 'nullable|string',
        'starts_at'   => 'required|date',
        'visitor_id'  => 'nullable|string',
    ]);

    $start = Carbon::parse($data['starts_at'])->timezone('Asia/Tokyo');
    if ($start->lt(Carbon::now('Asia/Tokyo')->addMinutes(5))) {
        return response()->json(['message'=>'現在以降の時刻で予約してください'], 422);
    }

    $room = 'r_'.Str::uuid();
    $id = DB::table('appointments')->insertGetId([
        'lawyer_id'=>$data['lawyer_id'],
        'client_name'=>$data['client_name'],
        'client_email'=>$data['client_email'] ?? '',
        'client_phone'=>$data['client_phone'] ?? '',
        'starts_at'=>$start->clone()->timezone('UTC'), // DBはUTC保存推奨
        'room'=>$room,
        'status'=>'booked',
        'visitor_id'=>$data['visitor_id'] ?? Str::uuid(),
        'created_at'=>now(), 'updated_at'=>now(),
    ]);

    // SPAのルータに合わせて「相対パス」を返す（絶対URLはフロントでoriginを足す）
    return response()->json([
        'appointmentId'=>$id,
        'room'=>$room,
        'clientJoinPath'=>"/wait?aid=$id",
        'hostJoinPath'  =>"/host?aid=$id",
    ]);
});
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
Route::get('/appointments/{id}', function ($id) {
    $row = DB::table('appointments')->where('id',$id)
        ->first(['room','starts_at','status']);
    abort_if(!$row, 404);
    return response()->json($row);
})->whereNumber('id');

Route::post('/dev/token', function (Illuminate\Http\Request $r) {
    $room = $r->input('room');
    $identity = $r->input('identity') ?? ('user-'.Str::uuid());
    if (!$room) return response()->json(['error'=>'room required'], 400);

    $opts  = (new AccessTokenOptions())->setIdentity($identity)->setTtl(3600);
    $grant = (new VideoGrant())->setRoomJoin(true)->setRoomName($room)->setCanPublish(true)->setCanSubscribe(true);

    $jwt = (new AccessToken(env('LIVEKIT_API_KEY'), env('LIVEKIT_API_SECRET')))
        ->init($opts)->setGrant($grant)->toJwt();

    return response()->json([
        'token' => $jwt,
        'url'   => env('LIVEKIT_URL'),
    ]);
});


