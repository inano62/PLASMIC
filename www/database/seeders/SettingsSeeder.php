<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('settings')->upsert([
            ['key' => 'site.name',          'value' => 'Regal'],
            ['key' => 'hero.title',         'value' => 'ワンクリックで、オンライン面談'],
            ['key' => 'plan.simple.price',  'value' => '3000'],
        ], ['key'], ['value']);
    }
}
