<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timeslots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->timestampTz('start_at');
            $table->timestampTz('end_at');
            $table->enum('status', ['open','reserved'])->default('open');
            // 予約テーブル名に合わせてどちらか：appointments を使うなら下行で OK
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id','start_at','end_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timeslots');
    }
};
