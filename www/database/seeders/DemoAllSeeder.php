<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DemoAllSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // ---------- 0) Plans ----------
        DB::table('plans')->upsert([
            ['code'=>'lite','name'=>'ライト','price_month'=>3300,'price_year'=>33000,'stripe_price_id_month'=>null,'stripe_price_id_year'=>null,'created_at'=>$now,'updated_at'=>$now],
            ['code'=>'pro','name'=>'プロ','price_month'=>19800,'price_year'=>198000,'stripe_price_id_month'=>null,'stripe_price_id_year'=>null,'created_at'=>$now,'updated_at'=>$now],
        ], ['code'], ['name','price_month','price_year','stripe_price_id_month','stripe_price_id_year','updated_at']);

        // ---------- 1) Users ----------
        // 管理者
        DB::table('users')->updateOrInsert(
            ['email' => 'admin@example.com'],
            ['name'=>'Admin','password'=>Hash::make('password'),'role'=>'admin','account_type'=>'admin','email_verified_at'=>$now,'created_at'=>$now,'updated_at'=>$now]
        );

        // オーナー10名（ログイン用メールは jemesouviensX@email.com）
        $ownerEmails = [];
        $owners = [];
        for ($i=1; $i<=10; $i++) {
            $ownerEmails[$i] = "jemesouviens{$i}@email.com";
            $owners[] = [
                'name' => "Owner {$i}",
                'email'=> $ownerEmails[$i],
                'password'=> Hash::make('password'),
                'role'=>'owner',
                'account_type'=>'pro',
                'email_verified_at'=>$now,
                'created_at'=>$now,
                'updated_at'=>$now,
            ];
        }
        DB::table('users')->insertOrIgnore($owners);

        // 顧客20名
        $clients = [];
        for ($i=1; $i<=20; $i++) {
            $clients[] = [
                'name'=>"Customer {$i}",
                'email'=>"customer{$i}@example.com",
                'password'=>Hash::make('password'),
                'role'=>'client',
                'account_type'=>'client',
                'email_verified_at'=>$now,
                'created_at'=>$now,
                'updated_at'=>$now,
            ];
        }
        DB::table('users')->insertOrIgnore($clients);

        // ---------- 2) Tenants ----------
        $types  = ['司法書士','行政書士','社労士','弁護士'];
        $regions= ['北海道','東京','神奈川','愛知','大阪','兵庫','福岡','熊本'];

        $slugOf = fn($i)=> match ($i) {
            1=>'demo', 2=>'gyousei', 3=>'sharoushi', 4=>'bengoshi',
            default => "office{$i}"
        };

        $tenantsRows = [];
        for ($i=1; $i<=10; $i++) {
            $slug = $slugOf($i);
            $ownerId = DB::table('users')->where('email',$ownerEmails[$i])->value('id');
            $tenantsRows[] = [
                'slug'=>$slug,
                'owner_user_id'=>$ownerId,
                'display_name'=>[1=>'PLASMIC 法務オフィス',2=>'さくら行政書士事務所',3=>'ひまわり社労士事務所',4=>'青空法律事務所'][$i] ?? "サンプル事務所 {$i}",
                'type'=>$types[($i-1)%count($types)],
                'region'=>$regions[$i%count($regions)],
                'home_url'=>null,
                'plan'=>'pro',
                'stripe_customer_id'=>null,
                'stripe_connect_id'=>null,
                'created_at'=>$now,'updated_at'=>$now,
            ];
        }
        DB::table('tenants')->insertOrIgnore($tenantsRows);
        $tenants = DB::table('tenants')->orderBy('id')->get();

        // ---------- 3) tenant_users ----------
        $tu = [];
        foreach ($tenants as $idx => $t) {
            $ownerId = DB::table('users')->where('email',$ownerEmails[$idx+1])->value('id');
            if ($ownerId) {
                $tu[] = ['tenant_id'=>$t->id,'user_id'=>$ownerId,'role'=>'owner','created_at'=>$now,'updated_at'=>$now];
            }
            $staffId = DB::table('users')->where('email',"customer".($idx+1)."@example.com")->value('id');
            if ($staffId) {
                $tu[] = ['tenant_id'=>$t->id,'user_id'=>$staffId,'role'=>'staff','created_at'=>$now,'updated_at'=>$now];
            }
        }
        DB::table('tenant_users')->insertOrIgnore($tu);

        // ---------- 4) site_settings ----------
        $ss = [];
        foreach ($tenants as $t) {
            $ss[] = [
                'tenant_id'=>$t->id,
                'brand_color'=>'#6b42f3',
                'accent_color'=>'#b26cf8',
                'logo_url'=>null,
                'hero_title'=>'予約・決済・ビデオ・HPをひとつに',
                'hero_sub'=>'専門性の高い現場向けのオールインワン。',
                'contact_email'=>"contact{$t->id}@example.com",
                'public_on'=>true,
                'created_at'=>$now,'updated_at'=>$now,
            ];
        }
        DB::table('site_settings')->insertOrIgnore($ss);

        // ---------- 5) subscriptions（plansにFKする） ----------
        $planIdLite = DB::table('plans')->where('code','lite')->value('id');
        $planIdPro  = DB::table('plans')->where('code','pro')->value('id');
        $subs = [];
        foreach ($tenants as $t) {
            $subs[] = [
                'tenant_id'=>$t->id,
                'plan_id'=> ($t->id % 2 === 0) ? $planIdPro : $planIdLite,
                'stripe_sub_id'=>null,
                'status'=>'active',
                'current_period_end'=>now()->addMonth(),
                'created_at'=>$now,'updated_at'=>$now,
            ];
        }
        DB::table('subscriptions')->insertOrIgnore($subs);

        // ---------- 6) timeslots（AM/PM：10:00,14:00の60分 open） ----------
        $ts = [];
        foreach ($tenants as $t) {
            for ($d=0; $d<7; $d++) {
                foreach ([10,14] as $h) {
                    $start = Carbon::today()->addDays($d)->setTime($h,0);
                    $end   = (clone $start)->addMinutes(60);
                    $ts[] = [
                        'tenant_id'=>$t->id,'start_at'=>$start,'end_at'=>$end,
                        'status'=>'open','created_at'=>$now,'updated_at'=>$now
                    ];
                }
            }
        }
        DB::table('timeslots')->insertOrIgnore($ts);

        // ---------- 7) reservations + payments ----------
        $customers = DB::table('users')->where('role','client')->pluck('id')->all();
        $reservations = []; $payments = []; $ci = 0;
        foreach ($tenants as $t) {
            for ($k=0; $k<2; $k++) {
                $start = Carbon::today()->addDays(rand(0,6))->setTime([10,14][rand(0,1)],0);
                $end   = (clone $start)->addMinutes(60);
                $id = (string) Str::uuid();
                $paid = ($k % 2) === 0;

                $reservations[] = [
                    'id'=>$id,'tenant_id'=>$t->id,
                    'customer_user_id'=>$customers[$ci++ % max(1,count($customers))],
                    'customer_name'=>null,'email'=>null,
                    'start_at'=>$start,'end_at'=>$end,'duration_min'=>60,
                    'price_jpy'=>5500,'amount'=>$paid?5500:0,
                    'payment_status'=>$paid?'paid':'unpaid',
                    'stripe_payment_intent_id'=>$paid?('pi_'.Str::random(20)):null,
                    'room_name'=>'room-'.Str::random(8),'meeting_url'=>null,
                    'scheduled_at'=>$now,'status'=>$paid?'paid':'booked',
                    'host_code'=>Str::upper(Str::random(8)),
                    'guest_code'=>Str::upper(Str::random(8)),
                    'created_at'=>$now,'updated_at'=>$now,
                ];

                if ($paid) {
                    $payments[] = [
                        'reservation_id'=>$id,'provider'=>'stripe',
                        'checkout_session_id'=>'cs_'.Str::random(24),
                        'payment_intent'=>'pi_'.Str::random(24),
                        'status'=>'succeeded','created_at'=>$now,'updated_at'=>$now,
                    ];
                }
            }
        }
        if ($reservations) DB::table('reservations')->insertOrIgnore($reservations);
        if ($payments) DB::table('payments')->insertOrIgnore($payments);

        // ---------- 8) inquiries ----------
        $inq = [];
        for ($i=1; $i<=5; $i++) {
            $inq[] = [
                'site_slug'=>'demo','name'=>"問合せ {$i}",'email'=>"lead{$i}@example.com",
                'phone'=>null,'address'=>null,'topic'=>'相談','message'=>'料金と空き枠について知りたいです。',
                'preferred_at'=>now()->addDays($i),'status'=>'new',
                'created_at'=>$now,'updated_at'=>$now,
            ];
        }
        DB::table('inquiries')->insertOrIgnore($inq);
    }
}
