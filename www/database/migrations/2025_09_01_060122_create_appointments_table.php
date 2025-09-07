<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // まだ appointments が無い場合はフル定義で作成
        if (!Schema::hasTable('appointments')) {
            Schema::create('appointments', function (Blueprint $t) {
                $t->id();

                $t->unsignedBigInteger('tenant_id')->index();
                $t->unsignedBigInteger('lawyer_user_id')->nullable()->index();
                $t->unsignedBigInteger('client_user_id')->nullable()->index();

                $t->dateTime('starts_at')->index();
                $t->dateTime('ends_at')->nullable();

                $t->string('room_name')->nullable();
                $t->enum('status', ['pending','booked','cancelled','finished'])->default('pending')->index();
                $t->integer('price_jpy')->default(0);
                $t->string('stripe_payment_intent_id')->nullable()->index();

                // 公開予約フォーム由来の付加情報
                $t->string('client_name')->nullable();
                $t->string('client_email')->nullable();
                $t->string('client_phone')->nullable();
                $t->string('purpose_title')->nullable();
                $t->text('purpose_detail')->nullable();
                $t->string('visitor_id')->nullable()->index();

                $t->timestamps();
            });
            return;
        }

        // 既存テーブルがある場合は ALTER（存在チェックしながら）
        Schema::table('appointments', function (Blueprint $t) {
            // 型変更（->change は doctrine/dbal が必要）
            if (Schema::hasColumn('appointments', 'tenant_id')) {
                $t->unsignedBigInteger('tenant_id')->index()->change();
            } else {
                $t->unsignedBigInteger('tenant_id')->index()->after('id');
            }

            if (Schema::hasColumn('appointments', 'lawyer_user_id')) {
                $t->unsignedBigInteger('lawyer_user_id')->nullable()->index()->change();
            } else {
                $t->unsignedBigInteger('lawyer_user_id')->nullable()->index();
            }

            if (Schema::hasColumn('appointments', 'client_user_id')) {
                $t->unsignedBigInteger('client_user_id')->nullable()->index()->change();
            } else {
                $t->unsignedBigInteger('client_user_id')->nullable()->index();
            }

            if (Schema::hasColumn('appointments', 'starts_at')) {
                $t->dateTime('starts_at')->index()->change();
            } else {
                $t->dateTime('starts_at')->index();
            }

            if (Schema::hasColumn('appointments', 'ends_at')) {
                $t->dateTime('ends_at')->nullable()->change();
            } else {
                $t->dateTime('ends_at')->nullable();
            }

            if (!Schema::hasColumn('appointments', 'room_name')) {
                $t->string('room_name')->nullable();
            }

            if (Schema::hasColumn('appointments', 'status')) {
                $t->enum('status', ['pending','booked','cancelled','finished'])->default('pending')->index()->change();
            } else {
                $t->enum('status', ['pending','booked','cancelled','finished'])->default('pending')->index();
            }

            if (Schema::hasColumn('appointments', 'price_jpy')) {
                $t->integer('price_jpy')->default(0)->change();
            } else {
                $t->integer('price_jpy')->default(0);
            }

            if (!Schema::hasColumn('appointments', 'stripe_payment_intent_id')) {
                $t->string('stripe_payment_intent_id')->nullable()->index();
            }

            // 追加フィールド（無ければ追加）
            if (!Schema::hasColumn('appointments','client_name'))    $t->string('client_name')->nullable();
            if (!Schema::hasColumn('appointments','client_email'))   $t->string('client_email')->nullable();
            if (!Schema::hasColumn('appointments','client_phone'))   $t->string('client_phone')->nullable();
            if (!Schema::hasColumn('appointments','purpose_title'))  $t->string('purpose_title')->nullable();
            if (!Schema::hasColumn('appointments','purpose_detail')) $t->text('purpose_detail')->nullable();
            if (!Schema::hasColumn('appointments','visitor_id'))     $t->string('visitor_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        // 破壊的に Drop するより、安全に“今回追加した列”だけ戻す
        Schema::table('appointments', function (Blueprint $t) {
            foreach ([
                         'client_name','client_email','client_phone',
                         'purpose_title','purpose_detail','visitor_id',
                         'stripe_payment_intent_id','room_name',
                     ] as $col) {
                if (Schema::hasColumn('appointments', $col)) {
                    $t->dropColumn($col);
                }
            }
        });

        // 型変更の完全なロールバックは環境差が出やすいのでここでは行わない（必要なら追加で用意）
    }
};
