<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InquiriesSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [];
        for ($i=1; $i<=5; $i++) {
            $rows[] = [
                'site_slug' => 'demo',
                'name' => "問合せ {$i}",
                'email'=> "lead{$i}@example.com",
                'phone'=> null,
                'address'=> null,
                'topic'=> '相談',
                'message'=> '料金と空き枠について知りたいです。',
                'preferred_at'=> now()->addDays($i),
                'status'=> 'new',
                'created_at'=> now(),
                'updated_at'=> now(),
            ];
        }
        DB::table('inquiries')->insert($rows);
    }
}
