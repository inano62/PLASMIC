<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;                 // ← これを追加
use Illuminate\Support\Facades\Hash; // ← パスワード用

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // id=1 の先生ユーザを1件だけ作成（あれば更新）
        User::updateOrCreate(
            ['id' => 1],
            [
                'name'              => 'ダミー先生',
                'email'             => 'pro@example.com',
                'email_verified_at' => now(),
                'password'          => Hash::make('password'), // 適当でOK
            ]
        );
    }
}
