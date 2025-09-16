<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class PlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('plans')->upsert([
            ['code'=>'lite', 'name'=>'ライト', 'price_month'=>3300,  'price_year'=>33000,  'stripe_price_id_month'=>null, 'stripe_price_id_year'=>null, 'created_at'=>now(), 'updated_at'=>now()],
            ['code'=>'pro',  'name'=>'プロ',  'price_month'=>19800, 'price_year'=>198000, 'stripe_price_id_month'=>null, 'stripe_price_id_year'=>null, 'created_at'=>now(), 'updated_at'=>now()],
        ], ['code'], ['name','price_month','price_year','stripe_price_id_month','stripe_price_id_year','updated_at']);
    }
}
