<?php
namespace App\Http\Controllers;

use App\Models\CallLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CallLogController extends Controller
{
    public function store(Request $r)
    {
        $v = $r->validate([
            'room'      => 'required|string',
            'type'      => 'required|string', // start|end|heartbeat|error|silence
            'ts'        => 'nullable|date',   // クライアント時刻
            'message'   => 'nullable|string',
            'extra'     => 'nullable|array',
            'event_id'  => 'nullable|string', // 冪等化用（uuid）
        ]);

        $now = now();
        $call = CallLog::firstOrNew([
            'room_name' => $v['room'],
            'ended_at'  => null,
        ]);

        // 初回開始
        if ($v['type'] === 'start') {
            if (!$call->exists) {
                $call->room_name    = $v['room'];
                $call->host_user_id = $r->user()?->id;
                $call->started_at   = $now;
            } elseif (!$call->started_at) {
                $call->started_at = $now;
            }
        }

        // 進行中ハートビート
        if ($v['type'] === 'heartbeat') {
            // ここで last_seen みたいなメタを刻む
            $meta = $call->meta ?? [];
            $meta['last_seen_at'] = $now->toIso8601String();
            $call->meta = $meta;
        }

        // 終了
        if ($v['type'] === 'end') {
            if (!$call->started_at) $call->started_at = $now; // 救済
            if (!$call->ended_at)   $call->ended_at   = $now;

            // ← ここを“絶対値の整数”で
            $call->duration_sec = $call->started_at
                ? $call->started_at->diffInSeconds($call->ended_at, true)  // true = 絶対値
                : null;
        }

        // 異常系・無音検知など
        if (in_array($v['type'], ['error','silence'])) {
            $meta = $call->meta ?? [];
            $meta['events'][] = [
                'type'    => $v['type'],
                'ts'      => ($v['ts'] ?? $now)->toString(),
                'message' => $v['message'] ?? null,
                'extra'   => $v['extra'] ?? null,
                'event_id'=> $v['event_id'] ?? null,
            ];
            $call->meta = $meta;
        }

        $call->save();
//        return response()->json(['ok' => true, 'id' => $call->id]);
        return response()->json([
            'ok'   => true,
            'id'   => $call->id,
            'data' => $call->fresh()->toArray(),  // ← 実際に保存された列を確認
        ], 200);
    }
}
