<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $t) {
            // SQLite対策: 既存チェックしてから追加
            if (!Schema::hasColumn('reservations', 'customer_user_id')) {
                $t->unsignedBigInteger('customer_user_id')->nullable()->index();
            }
            if (!Schema::hasColumn('reservations', 'appointment_id')) {
                $t->unsignedBigInteger('appointment_id')->nullable()->index();
            }
            if (!Schema::hasColumn('reservations', 'status')) {
                // enumはDBごとに差があるため、文字列で運用し index を付ける
                $t->string('status', 20)->default('unpaid')->index();
            }
            if (!Schema::hasColumn('reservations', 'guest_code')) {
                $t->string('guest_code')->nullable()->index();
            }

            // tenant_id の change は SQLite では失敗しやすいのでスキップ
            // 必要なら doctrine/dbal を入れて別途 migrate する
            // composer require doctrine/dbal
            // その後: $t->unsignedBigInteger('tenant_id')->change();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $t) {
            if (Schema::hasColumn('reservations', 'guest_code')) {
                $t->dropColumn('guest_code');
            }
            if (Schema::hasColumn('reservations', 'status')) {
                $t->dropColumn('status');
            }
            if (Schema::hasColumn('reservations', 'appointment_id')) {
                $t->dropColumn('appointment_id');
            }
            if (Schema::hasColumn('reservations', 'customer_user_id')) {
                $t->dropColumn('customer_user_id');
            }
        });
    }
};
