<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TimeslotsSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [];
        for ($tenantId=1; $tenantId<=10; $tenantId++) {
            for ($d=0; $d<7; $d++) {
                foreach ([10, 14] as $hour) {
                    $start = Carbon::today()->addDays($d)->setTime($hour, 0);
                    $end   = (clone $start)->addMinutes(60);
                    $rows[] = [
                        'tenant_id' => $tenantId,
                        'start_at'  => $start,
                        'end_at'    => $end,
                        'status'    => 'open',
                        'created_at'=> now(),
                        'updated_at'=> now(),
                    ];
                }
            }
        }
        DB::table('timeslots')->insert($rows);
    }
}
