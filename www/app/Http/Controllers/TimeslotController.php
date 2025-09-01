<?php
class TimeslotController extends Controller {
    public function listOpen(int $tenantId) {
        $from = request('from'); // ISO文字列
        $to   = request('to');
        return \App\Models\Timeslot::where('tenant_id',$tenantId)
            ->where('status','open')
            ->when($from, fn($q)=>$q->where('start_at','>=',$from))
            ->when($to, fn($q)=>$q->where('end_at','<=',$to))
            ->orderBy('start_at')->get(['id','start_at','end_at']);
    }
}
