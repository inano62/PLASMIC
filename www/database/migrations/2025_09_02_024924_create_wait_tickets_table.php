<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wait_tickets', function (Blueprint $t) {
            $t->uuid('id')->primary();                 // uuid
            $t->unsignedBigInteger('reservation_id');  // FK (booking)
            $t->text('token_jwt');                     // HS256署名済みの短命JWT
            $t->string('otp_code', 10);                // 6桁想定
            $t->timestamp('otp_expires_at');
            $t->timestamps();

            $t->foreign('reservation_id')
                ->references('id')->on('reservations')
                ->cascadeOnDelete();

            $t->index(['reservation_id']);
            $t->index(['otp_code']); // 逆引き用
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wait_tickets');
    }
};
