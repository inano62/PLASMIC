<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        // plan_id: pro=2, lite=1（PlansSeeder順）
        $rows = [];
        for ($tenantId=1; $tenantId<=10; $tenantId++) {
            $rows[] = [
                'tenant_id' => $tenantId,
                'plan_id'   => $tenantId % 2 === 0 ? 2 : 1,
                'stripe_sub_id' => null,
                'status'    => 'active',
                'current_period_end' => now()->addMonth(),
                'created_at'=> $now,
                'updated_at'=> $now,
            ];
        }
        DB::table('subscriptions')->insert($rows);
    }
}
