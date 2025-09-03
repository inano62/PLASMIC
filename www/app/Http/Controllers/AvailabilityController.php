<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\Reservation;

class AvailabilityController extends Controller {
    public function index(Request $r) {
        $from = Carbon::parse($r->query('from', now()->toIso8601String()))->second(0)->millisecond(0);
        $days = (int) $r->query('days', 7);
        $duration = (int) $r->query('duration', 30);
        $to = $from->copy()->addDays($days);

        // 既存予約（キャンセル以外）
        $reservations = Reservation::where('status','!=','canceled')
            ->whereBetween('start_at', [$from->copy()->subHours(1), $to->copy()->addHours(1)])
            ->get(['start_at','end_at']);

        // 30分境界に繰り上げ
        $cur = $from->copy();
        $m = (int)$cur->minute;
        if ($m % 30 !== 0) $cur->minute($m + (30 - $m % 30))->second(0);

        $slots = [];
        while ($cur->lt($to)) {
            $start = $cur->copy();
            $end   = $cur->copy()->addMinutes($duration);
            $busy = $reservations->first(fn($v)=> $v->start_at < $end && $v->end_at > $start);
            if (!$busy && $start->gt(now()->addMinutes(4))) {
                $slots[] = $start->toIso8601String();
            }
            $cur->addMinutes(30);
        }
        return response()->json(['slots'=>$slots]);
    }
}
