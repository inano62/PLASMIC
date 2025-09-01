<?php

// database/migrations/2025_09_01_000000_create_reservations.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
public function up(): void {
Schema::create('reservations', function (Blueprint $t) {
$t->uuid('id')->primary();
    $t->foreignId('tenant_id')->nullable()->constrained();
    $t->foreignId('customer_user_id')->nullable()->constrained('users');
    $t->timestamp('start_at')->nullable();
    $t->timestamp('end_at')->nullable();
    $t->integer('amount')->default(0);               // 税込
    $t->string('stripe_payment_intent_id')->nullable();
$t->string('room_name')->index();
$t->timestamp('scheduled_at');
$t->unsignedInteger('duration_min')->default(30);
$t->integer('price_jpy')->default(0);
$t->enum('status', ['pending','paid','canceled'])->default('pending');
$t->string('host_code')->unique();
$t->string('guest_code')->unique();
$t->string('host_name')->nullable();
$t->string('guest_name')->nullable();
$t->string('guest_email')->nullable();
$t->timestamps();
});

Schema::create('payments', function (Blueprint $t) {
$t->id();
$t->uuid('reservation_id');
$t->string('provider')->default('stripe');
$t->string('checkout_session_id')->nullable();
$t->string('payment_intent')->nullable();
$t->string('status')->default('created');
$t->timestamps();
});
}
public function down(): void {
Schema::dropIfExists('payments');
Schema::dropIfExists('reservations');
}
};
