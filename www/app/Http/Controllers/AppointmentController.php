<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Symfony\Component\HttpFoundation\Response;
use Firebase\JWT\Key;
use App\Models\Appointment;   // ★ 追加
use App\Models\Reservation;   // 使っていれば残す


class AppointmentController extends Controller
{
    /**
     * 指定テナント・先生の「空き枠」を返す
     * 既存予約（booked/pending とみなす）と重なる枠は除外
     *
     * クエリ:
     *   lawyer_id (必須) … 対象の先生
     *   days      (任意) … 何日分返すか（既定5, 最大31）
     *   step      (任意) … 枠の刻み分（既定30）
     *   duration  (任意) … 1枠の長さ分（既定30）
     *   open      (任意) … 営業開始 "HH:MM"（既定 "09:00"）
     *   close     (任意) … 営業終了 "HH:MM"（既定 "17:00"）
     *
     * 返却:
     *   [{ date: "YYYY-MM-DD", slots: [ISO8601, ...] }, ...]
     */
    public function publicSlots(Request $r, int $tenant)
    {
        $lawyerId = (int) $r->query('lawyer_id');
        abort_unless($lawyerId, 400, 'lawyer_id is required');

        $days     = min((int) $r->query('days', 5), 31);
        $step     = (int) $r->query('step', 30);
        $duration = (int) $r->query('duration', 30);
        $openStr  = $r->query('open',  '09:00');
        $closeStr = $r->query('close', '17:00');

        $now   = now(); // app.timezone
        $out   = [];

        for ($i = 0; $i < $days; $i++) {
            $day       = $now->copy()->startOfDay()->addDays($i);
            $dayStart  = $day->copy()->setTimeFromTimeString($openStr);
            $dayEnd    = $day->copy()->setTimeFromTimeString($closeStr);

            // その日の予約を取得（重なり検出用に starts/ends を取得）
            $apps = Appointment::query()
                ->where('tenant_id', $tenant)
                ->where('lawyer_user_id', $lawyerId)
                ->whereIn('status', ['booked','pending']) // pending も塞ぐならここに含める
                // 端がはみ出す予約も拾いたいので範囲に少し余白を付ける
                ->where(function ($q) use ($dayStart, $dayEnd) {
                    $q->whereBetween('starts_at', [$dayStart->copy()->subHour(), $dayEnd->copy()->addHour()])
                        ->orWhereBetween('ends_at',   [$dayStart->copy()->subHour(), $dayEnd->copy()->addHour()])
                        ->orWhere(function ($q2) use ($dayStart, $dayEnd) {
                            // 予約がこの日の全体を覆う場合
                            $q2->where('starts_at', '<=', $dayStart)
                                ->where('ends_at',   '>=', $dayEnd);
                        });
                })
                ->get(['starts_at','ends_at']);

            $slots = [];
            for ($t = $dayStart->copy(); $t->lt($dayEnd); $t->addMinutes($step)) {
                $s = $t->copy();
                $e = $t->copy()->addMinutes($duration);

                // 過去の枠は非表示（数分のバッファ）
                if ($s->lt($now->copy()->addMinutes(5))) continue;

                // どれかの予約と重なっていれば除外
                $overlap = $apps->contains(function ($ap) use ($s, $e) {
                    // [s,e) と [ap.start, ap.end) のオーバーラップ判定
                    return $s->lt($ap->ends_at) && $e->gt($ap->starts_at);
                });
                if (!$overlap) {
                    $slots[] = $s->toIso8601String(); // フロントは ISO で受ける想定
                }
            }

            $out[] = [
                'date'  => $day->format('Y-m-d'),
                'slots' => $slots,
            ];
        }

        return response()->json($out);
    }
    public function resolve(Request $req)
    {
        $aid    = $req->query('aid');
        $ticket = $req->query('ticket');

        if (!$aid && !$ticket) {
            return response()->json(['message' => 'aid/ticket が必要です'], Response::HTTP_BAD_REQUEST);
        }

        // ticket 優先: store() で {sub: reservation_id} で発行している想定
        if ($ticket) {
            try {
                $decoded = JWT::decode($ticket, new Key(env('TICKET_SECRET'), 'HS256'));
                $rid = $decoded->sub ?? null;
                if (!$rid) {
                    return response()->json(['message' => 'invalid ticket'], Response::HTTP_NOT_FOUND);
                }
                $res = Reservation::find($rid);
                if (!$res || empty($res->room_name)) {
                    return response()->json(['message' => 'invalid ticket'], Response::HTTP_NOT_FOUND);
                }
                return response()->json(['room' => $res->room_name]);
            } catch (\Throwable $e) {
                return response()->json(['message' => 'invalid ticket'], Response::HTTP_NOT_FOUND);
            }
        }

        // aid 指定：appointments から room_name を取り出す
        if ($aid) {
            $a = \App\Models\Appointment::find($aid);
            if (!$a || empty($a->room_name)) {
                return response()->json(['message' => 'invalid aid'], 404);
            }
            return response()->json(['room' => $a->room_name]);
        }
    }


    private function readAidFromJwt(string $jwt): ?string
    {
        $parts = explode('.', $jwt);
        if (count($parts) < 2) return null;
        $payload = json_decode($this->b64url($parts[1]), true);
        return $payload['aid'] ?? $payload['appointment_id'] ?? null;
    }

    private function b64url(string $data): string
    {
        $data = strtr($data, '-_', '+/');
        $pad = strlen($data) % 4;
        if ($pad > 0) $data .= str_repeat('=', 4 - $pad);
        return base64_decode($data) ?: '';
    }

    public function show(int $id) {
        $site = Site::find($id);
        if (!$site) {
            $site = new Site();
            // $site->id = $id; // ← SQLite の AUTOINCREMENT を尊重したいならセットしないでOK
            $site->title = 'Demo Site';
            $site->slug  = 'demo';
            $site->meta  = ['theme' => 'default'];
            $site->save();

            // Home ページを1枚作る
            $p = new Page();
            $p->site_id = $site->id;
            $p->title   = 'Home';
            $p->path    = '/';
            $p->sort    = 1;
            $p->save();
        }

        $pages = Page::where('site_id', $site->id)
            ->orderBy('sort')
            ->with(['blocks' => function($q){ $q->orderBy('sort'); }])
            ->get();

        return response()->json(['site' => $site, 'pages' => $pages]);
    }

    // 直近60分（士業ダッシュボード）
    public function nearby(Request $r) {
        $lawyerId = (int) $r->query('lawyer_id', 1);
        $from = now()->subMinutes(5);
        $to   = now()->addMinutes(60);

        $rows = Appointment::where('lawyer_user_id',$lawyerId)
            ->whereBetween('starts_at', [$from,$to])
            ->where('status','booked')
            ->orderBy('starts_at')
            ->get(['id','starts_at','room_name as room']);

        return response()->json($rows);
    }

    // 今後N日（士業ダッシュボード）
    public function upcoming(Request $r) {
        $lawyerId = (int) $r->query('lawyer_id', 1);
        $days = min((int)$r->query('days',14), 60);
        $from = now()->subMinutes(5);
        $to   = now()->addDays($days);

        $rows = Appointment::where('lawyer_user_id',$lawyerId)
            ->whereBetween('starts_at', [$from,$to])
            ->where('status','booked')
            ->orderBy('starts_at')
            ->get(['id','starts_at','room_name as room']);

        return response()->json($rows);
    }

    // 予約作成（支払い前の pending）
    public function store(Request $r){
        $data = $r->validate([
            'tenant_id'      => ['required','integer'],
            'lawyer_user_id' => ['required','integer'],
            'client_user_id' => ['required','integer'],
            'starts_at'      => ['required','date'],
            'duration_min'   => ['nullable','integer'],
            'price_jpy'      => ['nullable','integer'],
        ]);

        $a = Appointment::create([
            'tenant_id'      => (int)$data['tenant_id'],
            'lawyer_user_id' => (int)$data['lawyer_user_id'],
            'client_user_id' => (int)$data['client_user_id'],
            'starts_at'      => new Carbon($data['starts_at']),
            'ends_at'        => (new Carbon($data['starts_at']))->addMinutes((int)($data['duration_min'] ?? 30)),
            'status'         => 'pending',                 // 支払導入時はここから booked へ遷移
            'price_jpy'      => (int)($data['price_jpy'] ?? 0),
            'room_name'      => 'room_'.Str::lower(Str::random(10)),
        ]);
        // 予約直後に入室できる運用なら booked 化
        // $a->status = 'booked'; $a->save();

        // その場でチケット発行して返す（UIで即コピー可能）
        $jwt = JWT::encode(
            ['sub'=>$a->room_name,'exp'=>time()+1800],
            env('TICKET_SECRET','changeme'),'HS256'
        );

        return response()->json([
            'id'             => $a->id,
            'room'           => $a->room_name,
            'status'         => $a->status,
            'clientJoinPath' => "/wait?ticket={$a->id}",
            'hostJoinPath'   => "/host?aid={$a->id}",
        ], 201);
    }

    // 支払い成功などで確定
    public function confirm(int $id){
        $a = Appointment::findOrFail($id);
        $a->status = 'booked';
        $a->save();
        return response()->json(['ok'=>true]);
    }

    // チケット発行（ticket の sub は room_name に統一）
    public function issueTicket(int $id){
        $a = Appointment::findOrFail($id);
        $jwt = JWT::encode(
            ['sub'=>$a->room_name,'exp'=>time()+1800],
            env('TICKET_SECRET','changeme'),
            'HS256'
        );
        return response()->json([
            'ticket'         => $jwt,
            'clientJoinPath' => "/wait?ticket=$jwt",
            'hostJoinPath'   => "/host?aid=$id",
        ]);
    }

    // 今すぐビデオ（承認パネル用）
    public function instant(Request  $req, string $tenant)
    {

        $tenantId = resolveTenantId($tenant); // あなたの helper をそのまま利用

         $v = $req->validate([
            'lawyer_id'      => ['required','integer'],
//            'client_name'    => ['required','string','max:255'],
//            'client_email'   => ['nullable','email'],
//            'start_at'       => ['required','date'], // ← フロントは start_at で送っています
//            'visitor_id'     => ['nullable'],
//            'purpose_title'  => ['nullable','string'],
//            'purpose_detail' => ['nullable','string'],

        // 必要なら先生の所属や空き枠チェックをここで
        // ...
         ]);
        $ap = Appointment::create([
            'tenant_id'       => $tenantId,
            'lawyer_user_id'  => $v['lawyer_id'],     // 例: カラムが lawyer_user_id の場合
            // 'lawyer_id'    => $v['lawyer_id'],     // 例: カラムが lawyer_id の場合はこちらに変更
            'client_name'     => $v['client_name'],
            'client_email'    => $v['client_email'] ?? null,
            'starts_at'       => Carbon::parse($v['start_at']), // 例: カラムが starts_at の場合
            // 'start_at'     => Carbon::parse($v['start_at']), // 例: カラムが start_at の場合はこちら
            'status'          => 'booked',
        ]);

        // ひとまずダミーURLでOK（後でLiveKit等に置き換え）
        return response()->json([
            'appointmentId' => (string)$ap->id,
            'clientJoinPath'=> "/wait?aid={$ap->id}",
            'hostJoinPath'  => "/host?aid={$ap->id}",
        ]);
    }

    public function storeFromTenantPrefixed(array $data)
    {
        // $data['tenant_id'] はルート側で注入済み
        // 既存の store() のバリデーション/保存処理を流用
        $req = new \Illuminate\Http\Request($data);
        return $this->store($req); // 既存の store(Request $request) を再利用
    }

    public function myByVisitor(\Illuminate\Http\Request $req, \App\Models\Tenant $tenant)
    {
        $vid = $req->query('visitor_id');
        if (!$vid) return response()->json([]);
        $rows = \App\Models\Appointment::where('tenant_id',$tenant->id)
            ->where('visitor_id',$vid)->orderByDesc('starts_at')->limit(1)->get();
        return response()->json($rows);
    }

    public function myForTenant(Request $req, string $tenant) {
        $tenantId  = resolveTenantId($tenant);
        $visitorId = $req->query('visitor_id');
        abort_unless($visitorId, 400, 'visitor_id is required');

        return \App\Models\Appointment::query()
            ->where('tenant_id', $tenantId)
            ->where('visitor_id', $visitorId)
            ->latest('id')->take(1)->get();
    }

    public function storeForTenant(Request $req, int $tenant) {
        $tenantId = $tenant; // ← これでOK（ルート {tenant} は数値限定にしておくと尚良し）

        $lawyerId = (int) $req->input('lawyer_id', 1);
        $startIso = $req->input('start_at');
        abort_unless($startIso, 422, 'start_at is required');

        $starts = \Carbon\Carbon::parse($startIso)->setTimezone(config('app.timezone'));
        $ends   = (clone $starts)->addMinutes(30);

        $a = \App\Models\Appointment::create([
            'tenant_id'      => $tenantId,
            'lawyer_user_id' => $lawyerId,
            'client_user_id' => null,
            'client_name'    => $req->input('client_name', 'Guest'),
            'client_email'   => $req->input('client_email'),
            'client_phone'   => $req->input('client_phone'),
            'starts_at'      => $starts,
            'ends_at'        => $ends,
            'status'         => 'booked',
            'price_jpy'      => 0,
            'room_name'      => 'room_'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(10)),
            'visitor_id'     => (string) $req->input('visitor_id', 'public'),
            'purpose_title'  => $req->input('purpose_title', 'オンライン相談'),
            'purpose_detail' => $req->input('purpose_detail'),
        ]);

        return response()->json([
            'appointmentId' => (string)$a->id,
            'clientJoinPath'=> "/wait?room={$a->room_name}",
            'hostJoinPath'  => "/host?aid={$a->id}&room={$a->room_name}",
        ], 201);
    }
}
