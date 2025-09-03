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
        //
        // database/migrations/xxxx_add_unique_slot.php
        Schema::table('reservations', function (Blueprint $t) {
            $t->unique(['lawyer_id','start_at']); // 同じ士業×開始時刻は1件だけ
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        // database/migrations/xxxx_add_unique_slot.php
        Schema::table('reservations', function (Blueprint $t) {
            $t->unique(['lawyer_id','start_at']); // 同じ士業×開始時刻は1件だけ
        });

    }
};
