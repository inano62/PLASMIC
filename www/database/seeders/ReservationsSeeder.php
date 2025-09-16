<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ReservationsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $reservations = [];
        $payments = [];

        // 顧客ID: UsersSeederで 11..30 が顧客
        $customerIds = range(11, 30);
        $ci = 0;

        for ($tenantId=1; $tenantId<=10; $tenantId++) {
            // 2件ずつ
            for ($k=0; $k<2; $k++) {
                $start = Carbon::today()->addDays(rand(0,6))->setTime([9,11,13,15][rand(0,3)], 0);
                $end   = (clone $start)->addMinutes(60);

                $id = (string) Str::uuid();
                $paid = $k % 2 === 0;

                $reservations[] = [
                    'id' => $id,
                    'tenant_id' => $tenantId,
                    'customer_user_id' => $customerIds[$ci++ % count($customerIds)],
                    'customer_name' => null,
                    'email' => null,
                    'start_at' => $start,
                    'end_at'   => $end,
                    'duration_min' => 60,
                    'price_jpy' => 5500,
                    'amount' => $paid ? 5500 : 0,
                    'payment_status' => $paid ? 'paid' : 'unpaid',
                    'stripe_payment_intent_id' => $paid ? ('pi_'.Str::random(20)) : null,
                    'room_name' => 'room-'.Str::random(8),
                    'meeting_url' => null,
                    'scheduled_at' => $now,
                    'status' => $paid ? 'paid' : 'booked',
                    'host_code' => Str::upper(Str::random(8)),
                    'guest_code'=> Str::upper(Str::random(8)),
                    'created_at'=> $now,
                    'updated_at'=> $now,
                ];

                if ($paid) {
                    $payments[] = [
                        'reservation_id' => $id,
                        'provider' => 'stripe',
                        'checkout_session_id' => 'cs_'.Str::random(24),
                        'payment_intent' => 'pi_'.Str::random(24),
                        'status' => 'succeeded',
                        'created_at'=> $now,
                        'updated_at'=> $now,
                    ];
                }
            }
        }

        DB::table('reservations')->insert($reservations);
        if ($payments) DB::table('payments')->insert($payments);
    }
}
