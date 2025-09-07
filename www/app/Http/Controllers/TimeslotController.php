<?php
namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Carbon\CarbonInterval;

class TimeslotController extends Controller
{
    // /api/public/tenants/{id}/slots?lawyer_id=XX
    public function listOpen(Request $r, int $id)
    {
        // 先生ID（未指定なら owner/pro の先頭を採用）
        $lawyerId = (int) $r->query('lawyer_id', 0);
        if (!$lawyerId) {
            $lawyerId = (int) DB::table('tenant_users')
                ->where('tenant_id', $id)
                ->whereIn('role', ['owner','pro'])
                ->orderByRaw("CASE WHEN role='owner' THEN 0 ELSE 1 END")
                ->value('user_id');
        }

        $days = min(max((int)$r->query('days', 7), 1), 14);
        $tz   = $r->query('tz', 'Asia/Tokyo');
        $now  = Carbon::now($tz);

        // 営業時間テンプレ（平日 9:00-17:30／30分刻み）
        $hours = [
            1 => ['09:00','17:30'],
            2 => ['09:00','17:30'],
            3 => ['09:00','17:30'],
            4 => ['09:00','17:30'],
            5 => ['09:00','17:30'],
        ];
        $slot = CarbonInterval::minutes(30);

        $result = [];

        for ($i=0; $i<$days; $i++) {
            $day = $now->copy()->startOfDay()->addDays($i);
            $dow = $day->dayOfWeekIso;
            if (!isset($hours[$dow])) continue;

            [$startStr, $endStr] = $hours[$dow];
            $start = $day->copy()->setTimeFromTimeString($startStr);
            $end   = $day->copy()->setTimeFromTimeString($endStr);

            $slots = [];
            for ($t=$start->copy(); $t < $end; $t->add($slot)) {
                if ($t->lt($now)) continue;
                $tEnd = $t->copy()->add($slot);

                // その先生の「booked」面談と被っていないか
                $busy = DB::table('appointments')
                    ->where('tenant_id', $id)
                    ->where('lawyer_user_id', $lawyerId)
                    ->where('status', 'booked')
                    ->where('starts_at', '<', $tEnd)
                    ->where('ends_at',   '>', $t)
                    ->exists();

                if (!$busy) $slots[] = $t->toIso8601String();
            }

            if ($slots) $result[] = ['date' => $day->toDateString(), 'slots' => $slots];
        }

        return response()->json($result);
    }
    // TimeslotController.php
// app/Http/Controllers/TimeslotController.php
    public function listOpenForTenant(Request $req, int $tenantId)
    {
        $from = Carbon::parse($req->query('from', now()));
        $days = (int) $req->query('days', 7);
        $to   = (clone $from)->addDays($days);
        $duration = (int) $req->query('duration', 30); // 使うなら使う、不要なら無視でOK

        $rows = Timeslot::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'open')          // 状態名はプロジェクトに合わせて
            ->whereBetween('start_at', [$from, $to])
            ->orderBy('start_at')
            ->pluck('start_at');

        $slots = $rows
            ->map(fn($dt) => ($dt instanceof \Carbon\Carbon) ? $dt->toIso8601String() : (string) $dt)
            ->values();

        return response()->json(['slots' => $slots]);
    }
    public function storeForTenant(Request $request, Tenant $tenant)
    {
        // 既存 store() に丸投げするなら merge してから呼ぶ
        // $request->merge(['tenant_id' => $tenant->id]);
        // return $this->store($request);

        // ここに直接バリデーション & 作成でもOK
        $data = $request->validate([
            'lawyer_id'      => ['required', 'integer'],
            'client_name'    => ['required', 'string', 'max:255'],
            'client_email'   => ['nullable', 'email'],
            'client_phone'   => ['nullable', 'string', 'max:50'],
            'start_at'       => ['required', 'date'],
            'visitor_id'     => ['required', 'string', 'max:100'],
            'purpose_title'  => ['required', 'string', 'max:255'],
            'purpose_detail' => ['nullable', 'string'],
        ]);
        // 先生が同じテナントに属するかチェック（不一致は422）
        abort_unless(
            Lawyer::where('id', $data['lawyer_id'])->where('tenant_id', $tenant->id)->exists(),
            422, 'invalid lawyer for tenant'
        );

        $data['tenant_id'] = $tenant->id;
        $appt = Appointment::create($data);

        return response()->json([
            'appointmentId'  => $appt->id,
            'clientJoinPath' => "/wait?aid={$appt->id}",
            'hostJoinPath'   => "/host?aid={$appt->id}",
        ]);
    }

    public function myByVisitor(Request $request, Tenant $tenant)
    {
        $vid = $request->query('visitor_id');
        if (!$vid) return response()->json([]);

        $rows = Appointment::where('tenant_id', $tenant->id)
            ->where('visitor_id', $vid)
            ->orderByDesc('starts_at')
            ->limit(1)
            ->get();

        return response()->json($rows);
    }


}
