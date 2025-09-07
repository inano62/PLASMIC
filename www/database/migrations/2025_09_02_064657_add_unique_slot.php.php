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
        if (!Schema::hasTable('reservations')) return;

        // SQLite対策：既にあれば落としてから作る
        try { DB::statement('DROP INDEX IF EXISTS reservations_lawyer_id_start_at_unique'); } catch (\Throwable $e) {}

        Schema::table('reservations', function (Blueprint $t) {
            $t->unique(['lawyer_id','start_at'], 'reservations_lawyer_id_start_at_unique');
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        // SQLite対策：生SQLで確実に落とす
        try { DB::statement('DROP INDEX IF EXISTS reservations_lawyer_id_start_at_unique'); } catch (\Throwable $e) {}

    }
};
