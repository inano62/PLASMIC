<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 基本ユーティリティ（settings / cache / queue）
        |--------------------------------------------------------------------------
        */
        Schema::create('settings', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->text('value')->nullable();
            $t->timestamps();
        });

        // Laravel cache:table 相当
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

        // queue テーブル
        Schema::create('jobs', function (Blueprint $t) {
            $t->bigIncrements('id');
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

        /*
        |--------------------------------------------------------------------------
        | users / 認証系
        |--------------------------------------------------------------------------
        */
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->timestamp('email_verified_at')->nullable();
            $t->string('password');
            $t->enum('role', ['admin','owner','lawyer','client'])->default('client');
            $t->string('phone')->nullable();
            $t->string('account_type', 20)->default('client'); // client | pro | admin
            $t->string('stripe_customer_id')->nullable()->index();
            $t->string('subscription_status')->nullable()->index();
            $t->string('stripe_subscription_id')->nullable()->index();
            $t->string('stripe_default_pm')->nullable();
            $t->string('stripe_status')->nullable()->index();
            $t->rememberToken();
            $t->timestamps();
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

        /*
        |--------------------------------------------------------------------------
        | tenants 関連
        |--------------------------------------------------------------------------
        */
        Schema::create('tenants', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $t->string('display_name');
            $t->string('type')->nullable();
            $t->string('region')->nullable();
            $t->string('home_url')->nullable();
            $t->string('plan')->default('pro');
            $t->string('stripe_customer_id')->nullable();
            $t->string('stripe_connect_id')->nullable();
            $t->timestamps();
        });

        Schema::create('tenant_users', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('role', 20)->default('staff')->index();
            $t->timestamps();
            $t->unique(['tenant_id','user_id']);
        });

        /*
        |--------------------------------------------------------------------------
        | プラン / サブスクリプション
        |--------------------------------------------------------------------------
        */
        Schema::create('plans', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->integer('price_month')->nullable();
            $t->integer('price_year')->nullable();
            $t->string('stripe_price_id_month')->nullable();
            $t->string('stripe_price_id_year')->nullable();
            $t->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $t->string('stripe_sub_id')->nullable()->index();
            $t->string('status')->default('active');
            $t->timestamp('current_period_end')->nullable();
            $t->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | サイト設定
        |--------------------------------------------------------------------------
        */
        Schema::create('site_settings', function (Blueprint $t) {
            $t->foreignId('tenant_id')->primary()->constrained()->cascadeOnDelete();
            $t->string('brand_color')->nullable();
            $t->string('accent_color')->nullable();
            $t->string('logo_url')->nullable();
            $t->string('hero_title')->nullable();
            $t->text('hero_sub')->nullable();
            $t->string('contact_email')->nullable();
            $t->boolean('public_on')->default(true);
            $t->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | 予約 / 面談 / 決済 / 問い合わせ
        |--------------------------------------------------------------------------
        */
        Schema::create('appointments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('lawyer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('client_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('starts_at')->index();
            $t->dateTime('ends_at')->nullable();
            $t->string('room_name')->nullable();
            $t->enum('status', ['pending','booked','cancelled','finished'])->default('pending')->index();
            $t->integer('price_jpy')->default(0);
            $t->string('stripe_payment_intent_id')->nullable()->index();
            $t->string('client_name')->nullable();
            $t->string('client_email')->nullable();
            $t->string('client_phone')->nullable();
            $t->string('purpose_title')->nullable();
            $t->text('purpose_detail')->nullable();
            $t->string('visitor_id', 64)->nullable()->index();
            $t->timestamps();
        });

        Schema::create('timeslots', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->timestampTz('start_at');
            $t->timestampTz('end_at')->nullable();
            $t->enum('status', ['open','reserved'])->default('open');
            $t->timestamps();
            $t->unique(['tenant_id','start_at','end_at']);
        });

        Schema::create('reservations', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('customer_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('customer_name')->nullable();
            $t->string('email')->nullable();
            $t->timestamp('start_at')->nullable();
            $t->timestamp('end_at')->nullable();
            $t->unsignedInteger('duration_min')->default(30);
            $t->integer('price_jpy')->default(0);
            $t->integer('amount')->default(0);
            $t->string('payment_status')->default('unpaid');
            $t->string('stripe_payment_intent_id')->nullable()->index();
            $t->string('room_name')->nullable()->index();
            $t->string('meeting_url')->nullable();
            $t->timestamp('scheduled_at')->useCurrent();
            $t->enum('status', ['pending','booked','paid','canceled'])->default('booked');
            $t->string('host_code')->nullable()->unique();
            $t->string('guest_code')->nullable()->unique();
            $t->string('host_name')->nullable();
            $t->string('guest_name')->nullable();
            $t->string('guest_email')->nullable();
            $t->string('requester_email')->nullable()->index();
            $t->string('requester_phone')->nullable()->index();
            $t->timestamps();
        });

        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignUuid('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $t->string('provider')->default('stripe');
            $t->string('checkout_session_id')->nullable();
            $t->string('payment_intent')->nullable();
            $t->string('status')->default('created');
            $t->timestamps();
        });

        Schema::create('inquiries', function (Blueprint $t) {
            $t->id();
            $t->string('site_slug')->nullable();
            $t->string('name')->nullable();
            $t->string('email');
            $t->string('phone')->nullable();
            $t->string('address')->nullable();
            $t->string('topic')->nullable();
            $t->text('message');
            $t->dateTime('preferred_at')->nullable();
            $t->string('status')->default('new');
            $t->timestamps();
        });

        /*
        |--------------------------------------------------------------------------
        | CMS
        |--------------------------------------------------------------------------
        */
        Schema::create('sites', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('slug')->unique();
            $t->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
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
            $t->string('disk');
            $t->string('path', 255)->nullable()->change();
            $t->foreignId('site_id')->nullable()->constrained()->cascadeOnDelete();
            $t->string('original_name')->nullable();
            $t->string('mime')->nullable();
            $t->integer('size')->nullable();
            $t->longText('bytes')->nullable();
            $t->timestamps();
        });
        Schema::create('call_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $t->string('room_name')->index();
            $t->foreignId('host_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('guest_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->unsignedInteger('consultation_fee')->nullable();
            $t->string('checkout_session_id')->nullable()->index();
            $t->json('meta')->nullable();
            $t->timestamp('started_at');
            $t->timestamp('ended_at')->nullable();
            $t->unsignedInteger('duration_sec')->nullable();
            $t->string('outcome')->nullable(); // 'closed','no_show','reschedule' など
            $t->text('summary')->nullable();
            $t->timestamps();
        });

        Schema::create('call_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('call_log_id')->constrained()->cascadeOnDelete();
            $t->foreignId('sender_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('content');
            $t->timestamp('sent_at')->useCurrent();
        });
    }

    public function down(): void
    {
        // 依存の深い順に drop
        Schema::dropIfExists('blocks');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('sites');
        Schema::dropIfExists('media');

        Schema::dropIfExists('inquiries');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('timeslots');
        Schema::dropIfExists('appointments');

        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('tenant_users');
        Schema::dropIfExists('tenants');

        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');

        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');

        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('settings');

        Schema::dropIfExists('call_logs');
        Schema::dropIfExists('call_messages');
    }
};
