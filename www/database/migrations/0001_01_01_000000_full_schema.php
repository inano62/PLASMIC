<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1) 基本（jobs / cache / settings など先に作ってもOK）
        Schema::create('cache', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->mediumText('value');
            $t->integer('expiration');
        });
        Schema::create('cache_locks', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->string('owner');
            $t->integer('expiration');
        });

        Schema::create('jobs', function (Blueprint $t) {
            $t->id();
            $t->string('queue')->index();
            $t->longText('payload');
            $t->unsignedTinyInteger('attempts');
            $t->unsignedInteger('reserved_at')->nullable();
            $t->unsignedInteger('available_at');
            $t->unsignedInteger('created_at');
        });
        Schema::create('job_batches', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->string('name');
            $t->integer('total_jobs');
            $t->integer('pending_jobs');
            $t->integer('failed_jobs');
            $t->longText('failed_job_ids');
            $t->mediumText('options')->nullable();
            $t->integer('cancelled_at')->nullable();
            $t->integer('created_at');
            $t->integer('finished_at')->nullable();
        });
        Schema::create('failed_jobs', function (Blueprint $t) {
            $t->id();
            $t->string('uuid')->unique();
            $t->text('connection');
            $t->text('queue');
            $t->longText('payload');
            $t->longText('exception');
            $t->timestamp('failed_at')->useCurrent();
        });

        Schema::create('settings', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->text('value')->nullable();
            $t->timestamps();
        });

        // 2) users（ここで role 等も最初から作る＆複合indexを一回で作成）
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->timestamp('email_verified_at')->nullable();
            $t->string('password');
            // 追加分（後から ALTER しない）
            $t->enum('role', ['admin','lawyer','client'])->default('client');
            $t->string('phone')->nullable();
            $t->string('account_type', 20)->default('client'); // 'client' | 'pro' | 'admin'
            $t->string('stripe_customer_id')->nullable()->index();
            $t->rememberToken();
            $t->timestamps();

            // 複合 index は “この1回だけ” で作る
            $t->index(['email','role'], 'users_email_role_index');
        });

        Schema::create('password_reset_tokens', function (Blueprint $t) {
            $t->string('email')->primary();
            $t->string('token');
            $t->timestamp('created_at')->nullable();
        });
        Schema::create('sessions', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->foreignId('user_id')->nullable()->index();
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->longText('payload');
            $t->integer('last_activity')->index();
        });

        // 3) tenants（users を参照するので users の後）
        Schema::create('tenants', function (Blueprint $t) {
            $t->id();
            $t->foreignId('owner_user_id')->constrained('users');
            $t->string('display_name');
            $t->string('stripe_customer_id')->nullable();
            $t->string('stripe_connect_id')->nullable();
            $t->string('plan')->default('pro');
            $t->timestamps();
        });

        // 4) appointments（独立）
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

            // 予約フォームからの付加情報
            $t->string('client_name')->nullable();
            $t->string('client_email')->nullable();
            $t->string('client_phone')->nullable();
            $t->string('purpose_title')->nullable();
            $t->text('purpose_detail')->nullable();
            $t->string('visitor_id', 64)->nullable()->index();

            $t->timestamps();
        });

        // 5) reservations（tenants/users を参照するのでその後）
        Schema::create('reservations', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('customer_user_id')->nullable()->constrained('users')->nullOnDelete();

            $t->timestamp('start_at')->nullable();
            $t->timestamp('end_at')->nullable();

            $t->integer('amount')->default(0);
            $t->string('stripe_payment_intent_id')->nullable();
            $t->string('room_name')->index();

            // MariaDB 10.3 厳格モード対策
            $t->timestamp('scheduled_at')->useCurrent();

            $t->unsignedInteger('duration_min')->default(30);
            $t->integer('price_jpy')->default(0);
            $t->enum('status', ['pending','booked','paid','canceled'])->default('booked');

            $t->string('host_code')->unique();
            $t->string('guest_code')->unique();

            $t->string('host_name')->nullable();
            $t->string('guest_name')->nullable();
            $t->string('guest_email')->nullable();

            // 後続マイグレーションで要求されていた列（問い合わせ検索用）
            $t->string('requester_email')->nullable()->index();
            $t->string('requester_phone')->nullable()->index();

            $t->timestamps();
        });

        // 6) payments（reservations を参照するので後）
        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignUuid('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $t->string('provider')->default('stripe');
            $t->string('checkout_session_id')->nullable();
            $t->string('payment_intent')->nullable();
            $t->string('status')->default('created');
            $t->timestamps();
        });

        // 7) timeslots（appointments を参照）
        Schema::create('timeslots', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->timestampTz('start_at')->nullable();
            $t->timestampTz('end_at')->nullable();
            $t->enum('status', ['open','reserved'])->default('open');
            $t->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $t->timestamps();
            $t->unique(['tenant_id','start_at','end_at']);
        });

        // 8) wait_tickets（reservations を参照）※必ず reservations 後
        Schema::create('wait_tickets', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignUuid('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $t->text('token_jwt');
            $t->string('otp_code', 10)->index();
            $t->timestamp('otp_expires_at')->nullable();
            $t->string('status')->default('waiting');
            $t->timestamps();
        });

        // 9) CMS（sites → pages → blocks → media）
        Schema::create('sites', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('slug')->unique();
            $t->json('meta')->nullable();
            $t->timestamps();
        });
        Schema::create('pages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('site_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->string('path');
            $t->unsignedInteger('sort')->default(1);
            $t->longText('published_html')->nullable();
            $t->longText('published_json')->nullable();
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
            $t->unique(['site_id','path']);
        });
        Schema::create('blocks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('page_id')->constrained()->cascadeOnDelete();
            $t->string('type');
            $t->json('data')->nullable();
            $t->unsignedInteger('sort')->default(1);
            $t->timestamps();
        });
        Schema::create('media', function (Blueprint $t) {
            $t->id();
            $t->string('disk')->default('public');
            $t->string('path');
            $t->string('original_name')->nullable();
            $t->string('mime')->nullable();
            $t->integer('size')->nullable();
            $t->timestamps();
        });

        // 10) personal_access_tokens（Sanctum互換）
        Schema::create('personal_access_tokens', function (Blueprint $t) {
            $t->id();
            $t->morphs('tokenable');
            $t->text('name');
            $t->string('token', 64)->unique();
            $t->text('abilities')->nullable();
            $t->timestamp('last_used_at')->nullable();
            $t->timestamp('expires_at')->nullable()->index();
            $t->timestamps();
        });

        // 11) tenant_users（最後にユニーク制約）
        Schema::create('tenant_users', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('tenant_id')->index();
            $t->unsignedBigInteger('user_id')->index();
            $t->string('role', 20)->default('pro')->index(); // 'owner' | 'pro' | 'staff' | ...
            $t->timestamps();
            $t->unique(['tenant_id','user_id']);
        });

        // 12) 予約のユニーク（必要なら）
        Schema::table('reservations', function (Blueprint $t) {
            // 例: 弁護士×開始時刻の一意制約が必要なら列名に合わせて調整
            // $t->unique(['lawyer_id','start_at'], 'reservations_lawyer_id_start_at_unique');
        });
    }

    public function down(): void
    {
        // FK 依存の逆順で Drop
        foreach ([
                     'tenant_users','personal_access_tokens','media','blocks','pages','sites',
                     'wait_tickets','timeslots','payments','reservations','appointments',
                     'tenants','sessions','password_reset_tokens','users',
                     'settings','failed_jobs','job_batches','jobs','cache_locks','cache',
                 ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
