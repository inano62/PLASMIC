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
        Schema::table('users', function (Blueprint $t) {
            //
            $t->enum('role', ['admin','lawyer','client'])->default('client')->after('email');
            $t->string('phone')->nullable()->after('email_verified_at');
            $t->index(['email','role']);
            $t->string('account_type', 20)->default('client')->index(); // 'client' | 'pro' | 'admin'
            $t->string('stripe_customer_id')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
            Schema::dropIfExists('users');
        });
    }
};
