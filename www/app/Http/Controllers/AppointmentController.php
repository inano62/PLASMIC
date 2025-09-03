<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Reservation;
use Firebase\JWT\JWT;
use Symfony\Component\HttpFoundation\Response;
use Firebase\JWT\Key;


class AppointmentController extends Controller
{
    public function store(Request $r)
    {
        $data = $r->validate([
            'lawyer_id'      => ['required','integer'],
            'client_name'    => ['required','string','max:255'],
            'client_email'   => ['nullable','email','max:255'],
            'client_phone'   => ['nullable','string','max:50'],
            'start_at'       => ['required','date'],
            'visitor_id'     => ['required','string','max:64'],
            'purpose_title'  => ['required','string','max:255'],
            'purpose_detail' => ['nullable','string'],
        ]);

        // 次の30分にスナップ
        $start = Carbon::parse($data['start_at'])->second(0)->millisecond(0);
        $m = (int) $start->minute;
        if ($m % 30 !== 0) $start->minute($m + (30 - $m % 30));
        $duration = 30;
        $end = $start->copy()->addMinutes($duration);

        // 衝突チェック＆作成はトランザクションで
        $res = DB::transaction(function () use ($data, $start, $end) {
            $busy = Reservation::where('lawyer_id', $data['lawyer_id'])
                ->where('status','!=','canceled')
                ->where('start_at','<',$end)
                ->where('end_at','>',$start)
                ->lockForUpdate()
                ->exists();

            if ($busy) {
                return null; // 後で409にする
            }

            return Reservation::create([
                'id'               => (string) Str::uuid(),
                'tenant_id'        => null,
                'customer_user_id' => null,

                'start_at'     => $start,
                'end_at'       => $end,
                'scheduled_at' => $start,

                'amount'    => 0,
                'price_jpy' => 0,

                'room_name'  => 'room_' . Str::lower(Str::random(10)),
                'host_code'  => (string) Str::uuid(),
                'guest_code' => (string) Str::uuid(),

                'status' => 'booked', // ← pending でもOKだが入室想定なら booked が自然

                'host_name'      => null,
                'guest_name'     => $data['client_name'],
                'guest_email'    => $data['client_email'] ?? null,
                'purpose_title'  => $data['purpose_title'],
                'purpose_detail' => $data['purpose_detail'] ?? null,
                'requester_name'  => $data['client_name'],
                'requester_email' => $data['client_email'] ?? null,
                'requester_phone' => $data['client_phone'] ?? null,
            ]);
        });

        if (!$res) {
            // 次の空き30分を提案（最大 10 コマ先）
            $cand = $start->copy(); $found = null;
            for ($i=0; $i<10; $i++) {
                $cs = $cand->copy(); $ce = $cs->copy()->addMinutes(30);
                $b = Reservation::where('lawyer_id', $data['lawyer_id'])
                    ->where('status','!=','canceled')
                    ->where('start_at','<',$ce)->where('end_at','>',$cs)
                    ->exists();
                if (!$b) { $found = $cs; break; }
                $cand->addMinutes(30);
            }
            return response()->json([
                'error' => 'slot_conflict',
                'message' => '希望の時間は埋まっています',
                'suggested_start_at' => $found?->toIso8601String(),
            ], 409);
        }

        // ★ ここで ticket を発行（sub は Reservation の id）
        $ticket = JWT::encode(
            ['sub' => $res->id, 'exp' => time() + 900],
            env('TICKET_SECRET'),
            'HS256'
        );

        return response()->json([
            'appointmentId'  => (string) $res->id,
            'clientJoinPath' => '/wait?ticket=' . $ticket,
            'hostJoinPath'   => '/host?aid=' . $res->id,
        ], 201);
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

        // aid 指定
        $res = Reservation::find($aid);
        if (!$res || empty($res->room_name)) {
            return response()->json(['message' => 'invalid aid'], Response::HTTP_NOT_FOUND);
        }

        return response()->json(['room' => $res->room_name]);
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
}
