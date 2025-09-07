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
        $a = Appointment::findOrFail($id);
        return response()->json([
            'id'        => $a->id,
            'room'      => $a->room_name,
            'starts_at' => $a->starts_at,
            'status'    => $a->status,
        ]);
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

    public function storeForTenant(Request $req, string $tenant) {

        $tenantId = resolveTenantId($tenant);
        $lawyerId = (int) $req->input('lawyer_id', 1);
        $startIso = $req->input('start_at', now()->addMinutes(30)->toISOString());
        $payload = [
            'tenant_id'       => $tenantId,
            'lawyer_user_id'  => $lawyerId,
            'client_user_id'  => null,                      // 使っていないなら null 可（DBが許せば）
            'client_name'     => $req->input('client_name', 'Guest'),
            'client_email'    => $req->input('client_email'),
            'client_phone'    => $req->input('client_phone'),
            'starts_at'       => Carbon::parse($startIso),
            'ends_at'         => Carbon::parse($startIso)->addMinutes(30),
            'status'          => 'booked',
            'price_jpy'       => 0,
            'room_name'       => 'room_'.Str::lower(Str::random(10)),
            'visitor_id'      => (string) $req->input('visitor_id', 'public'),
            'purpose_title'   => $req->input('purpose_title', 'オンライン相談'),
            'purpose_detail'  => $req->input('purpose_detail'),
        ];

//        $data = $req->validate([
////            'lawyer_id'      => 'required|integer',
////            'client_name'    => 'required|string|max:100',
////            'client_email'   => 'nullable|email',
////            'client_phone'   => 'nullable|string|max:50',
////            'start_at'       => 'required|date',
////            'visitor_id'     => 'required|string|max:64',
////            'purpose_title'  => 'required|string|max:200',
////            'purpose_detail' => 'nullable|string',
//        ]);
//        $data['tenant_id'] = $tenantId;

        // ここで空き枠チェック → 競合なら 409 & suggested_start_at を返す
        // …

//        $a = \App\Models\Appointment::create($payload);
        $a = \App\Models\Appointment::create([
            'tenant_id'       => $tenantId,
            'lawyer_user_id'  => $lawyerId,
            'client_user_id'  => null,
            'client_name'     => $req->input('client_name', 'Guest'),
            'client_email'    => $req->input('client_email'),
            'client_phone'    => $req->input('client_phone'),
            'starts_at'       => \Carbon\Carbon::parse($startIso),
            'ends_at'         => \Carbon\Carbon::parse($startIso)->addMinutes(30),
            'status'          => 'booked',
            'price_jpy'       => 0,
            'room_name'       => 'room_'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(10)),
            'visitor_id'      => (string) $req->input('visitor_id', 'public'),
            'purpose_title'   => $req->input('purpose_title', 'オンライン相談'),
            'purpose_detail'  => $req->input('purpose_detail'),
        ]);
        // ★ ゲスト用 ticket（sub は room_name）
        $jwt = JWT::encode(
            [
                'sub'=>$a->room_name,
                'exp'=>time()+1800
            ],
            env('TICKET_SECRET','changeme'),
            'HS256'
        );

        return response()->json([
            'appointmentId' => (string)$a->id,
            'clientJoinPath'=> "/wait?room=$a->room_name",          // ← 統一
            'hostJoinPath'  => "/host?aid={$a->id}&room={$a->room_name}",
        ], 201);
//        return response()->json([
//            'appointmentId' => $a->id,
//            'clientJoinPath'=> "/wait?aid={$a->id}",
//            'hostJoinPath'  => "/host?aid={$a->id}",
//        ], 201);
    }
}
