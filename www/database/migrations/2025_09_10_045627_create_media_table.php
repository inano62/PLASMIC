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
        if (Schema::hasTable('media')) return;
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('disk')->default('public'); // storage/app/public
            $table->string('path');                    // 例: uploads/xxx.jpg
            $table->string('mime')->nullable();
            $table->integer('size')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
