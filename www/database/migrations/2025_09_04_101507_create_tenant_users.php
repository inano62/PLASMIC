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
        Schema::create('tenant_users', function (Blueprint $t) {
            //
            $t->id();
            $t->unsignedBigInteger('tenant_id')->index();
            $t->unsignedBigInteger('user_id')->index();
            $t->string('role', 20)->default('pro')->index(); // 'owner' | 'pro' | 'staff' | ...
            $t->timestamps();
            $t->unique(['tenant_id','user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_users', function (Blueprint $table) {
            //
            Schema::dropIfExists('tenant_users');
        });
    }
};
