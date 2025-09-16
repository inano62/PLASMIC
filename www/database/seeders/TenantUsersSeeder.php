<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class TenantUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        // 各テナントのオーナーを tenant_users にも登録
        $rows = [];
        for ($tenantId=1; $tenantId<=10; $tenantId++) {
            $rows[] = [
                'tenant_id' => $tenantId,
                'user_id'   => $tenantId, // ownerは1..10
                'role'      => 'owner',
                'created_at'=> $now,
                'updated_at'=> $now,
            ];
        }

        // スタッフ（ダミーで顧客ユーザーの一部をスタッフに）
        for ($tenantId=1; $tenantId<=10; $tenantId++) {
            $rows[] = [
                'tenant_id' => $tenantId,
                'user_id'   => 10 + $tenantId, // customer1..10 をstaffに
                'role'      => 'staff',
                'created_at'=> $now,
                'updated_at'=> $now,
            ];
        }

        DB::table('tenant_users')->insert($rows);
    }
}
