<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // オーナー10名（ownerロール）
        $owners = [];
        for ($i=1; $i<=10; $i++) {
            $owners[] = [
                'name' => "Owner {$i}",
                'email' => "owner{$i}@example.com",
                'password' => Hash::make('password'),
                'role' => 'owner',
                'account_type' => 'pro',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('users')->insert($owners);

        // 顧客20名（clientロール）
        $clients = [];
        for ($i=1; $i<=20; $i++) {
            $clients[] = [
                'name' => "Customer {$i}",
                'email' => "customer{$i}@example.com",
                'password' => Hash::make('password'),
                'role' => 'client',
                'account_type' => 'client',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('users')->insert($clients);

        // 管理者1名（任意）
        DB::table('users')->updateOrInsert(
            ['email'=>'admin@example.com'],
            ['name'=>'Admin', 'password'=>Hash::make('password'), 'role'=>'admin', 'account_type'=>'admin', 'updated_at'=>$now, 'created_at'=>$now]
        );
    }
}
