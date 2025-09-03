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
        Schema::table('appointments', function (Blueprint $t) {
            //
            if (!Schema::hasColumn('appointments', 'visitor_id')) {
                $t->string('visitor_id', 64)->nullable()->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $t) {
            if (Schema::hasColumn('appointments', 'visitor_id')) {
                $t->dropIndex(['visitor_id']);
                $t->dropColumn('visitor_id');
            }
        });
    }
};
