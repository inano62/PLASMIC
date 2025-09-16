<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class SiteSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();
        $rows = [];
        for ($tenantId=1; $tenantId<=10; $tenantId++) {
            $rows[] = [
                'tenant_id' => $tenantId,
                'brand_color' => '#6b42f3',
                'accent_color'=> '#b26cf8',
                'logo_url' => null,
                'hero_title' => '予約・決済・ビデオ・HPをひとつに',
                'hero_sub'   => '専門性の高い現場向けのオールインワン。',
                'contact_email' => "contact{$tenantId}@example.com",
                'public_on' => true,
                'created_at'=> $now,
                'updated_at'=> $now,
            ];
        }
        DB::table('site_settings')->insert($rows);
    }
}
