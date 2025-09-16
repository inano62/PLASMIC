<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TenantsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $now = now();

        // 士業タイプ/地域のサンプル
        $types  = ['司法書士','行政書士','社労士','弁護士'];
        $regions= ['北海道','東京','神奈川','愛知','大阪','兵庫','福岡','熊本'];

        // オーナーID 1..10 を各テナントの owner_user_id に割当
        $rows = [];
        for ($i=1; $i<=10; $i++) {
            $slug = match ($i) {
                1 => 'demo',
                2 => 'gyousei',
                3 => 'sharoushi',
                4 => 'bengoshi',
                default => "office{$i}",
            };
            $rows[] = [
                'slug' => $slug,
                'owner_user_id' => $i, // UsersSeederで1..10がowner
                'display_name' => [
                        1 => 'PLASMIC 法務オフィス',
                        2 => 'さくら行政書士事務所',
                        3 => 'ひまわり社労士事務所',
                        4 => '青空法律事務所',
                    ][$i] ?? "サンプル事務所 {$i}",
                'type' => $types[($i-1) % count($types)],
                'region' => $regions[$i % count($regions)],
                'home_url' => null,
                'plan' => 'pro',
                'stripe_customer_id' => null,
                'stripe_connect_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('tenants')->insert($rows);
    }
}
