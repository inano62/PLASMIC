<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class DevSeeder extends Seeder
{
    public function run(): void
    {
        // Users（既存あれば更新）
        $test = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name'              => 'Test User',
                'password'          => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        $pro = User::updateOrCreate(
            ['email' => 'pro@example.com'],
            [
                'name'              => '先生 太郎',
                'password'          => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // tenants
        DB::table('tenants')->updateOrInsert(
            ['display_name' => 'PLASMIC 法務オフィス'],
            [
                'owner_user_id' => $test->id,
                'plan'          => 'pro',
                'updated_at'    => now(),
                'created_at'    => now(),
            ]
        );

        $tenantId = DB::table('tenants')
            ->where('display_name', 'PLASMIC 法務オフィス')
            ->value('id');

        // tenant_users（紐づけ）
        DB::table('tenant_users')->updateOrInsert(
            ['tenant_id' => $tenantId, 'user_id' => $test->id],
            ['role' => 'owner', 'updated_at' => now(), 'created_at' => now()]
        );
        DB::table('tenant_users')->updateOrInsert(
            ['tenant_id' => $tenantId, 'user_id' => $pro->id],
            ['role' => 'pro', 'updated_at' => now(), 'created_at' => now()]
        );
    }
}
