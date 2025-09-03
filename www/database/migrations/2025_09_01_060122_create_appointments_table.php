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
        Schema::create('appointments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('lawyer_id');
            $t->uuid('visitor_id')->nullable()->index();
            $t->string('client_name');
            $t->string('client_email')->nullable();
            $t->string('client_phone')->nullable();
            $t->timestamp('starts_at'); // 予約開始時刻（UTC推奨）
            $t->string('room');         // LiveKitのroom名
            $t->enum('status', ['booked','done','canceled'])->default('booked');
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
