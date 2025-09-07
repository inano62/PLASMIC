<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Timeslot;
use Carbon\Carbon;

class TimeslotSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 1; // ← plasmic-law の ID に合わせる
        $start = Carbon::now()->ceilMinute(30)->setSecond(0);

        for ($i = 0; $i < 20; $i++) {
            Timeslot::updateOrCreate(
                ['tenant_id' => $tenantId, 'start_at' => (clone $start)->addMinutes(30*$i)],
                [
                    'end_at' => (clone $start)->addMinutes(30*($i+1)),
                    'status' => 'open',
                ]
            );
        }
    }
    // TimeslotController.php
    public function listOpenForTenant(Request $req, string $tenant) {
        $tenantId = resolveTenantId($tenant);
        $from = $req->query('from');
        $days = (int)$req->query('days', 7);
        $duration = (int)$req->query('duration', 30);
        $slots = $this->buildSlots($tenantId, $from, $days, $duration);
        return response()->json(['slots' => $slots]);
    }
}
