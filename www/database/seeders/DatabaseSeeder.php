<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlansSeeder::class,
            UsersSeeder::class,
            TenantsSeeder::class,
            TenantUsersSeeder::class,
            SiteSettingsSeeder::class,
            SubscriptionsSeeder::class,
            TimeslotsSeeder::class,
            ReservationsSeeder::class,
            InquiriesSeeder::class,
        ]);
    }
}
