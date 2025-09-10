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
        if (Schema::hasTable('pages')) return;
        Schema::create('pages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('site_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->string('path');               // 例: "/", "/about", "/blog/hello"
            $t->integer('sort')->default(1);
            $t->timestamps();
            $t->unique(['site_id','path']);   // 同一サイト内で一意
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
