<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $t) {
            $t->id();
            $t->foreignId('owner_user_id')->constrained('users');
            $t->string('display_name');
            $t->string('stripe_customer_id')->nullable();
            $t->string('stripe_connect_id')->nullable();
            $t->string('plan')->nullable(); // basic/pro/site
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
