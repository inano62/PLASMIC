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
        Schema::table('reservations', function (Blueprint $table) {
            //
            $table->string('purpose_title')->after('status');
            $table->text('purpose_detail')->nullable()->after('purpose_title');
            $table->string('requester_name')->after('guest_email');
            $table->string('requester_email')->nullable()->after('requester_name');
            $table->string('requester_phone')->nullable()->after('requester_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            //
            $table->dropColumn([
                'purpose_title','purpose_detail',
                'requester_name','requester_email','requester_phone'
            ]);
        });
    }
};
