<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SettingsSeeder::class, // 設定
            DevSeeder::class,      // 開発用データ（ユーザー/テナント/紐づけ）
        ]);
    }
}
