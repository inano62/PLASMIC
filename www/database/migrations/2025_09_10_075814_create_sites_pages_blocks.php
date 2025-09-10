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

        if (Schema::hasTable('sites')) return;
        Schema::create('sites', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('slug')->unique();           // 例: judist-sakai
            $t->json('meta')->nullable();           // SEOなど
            $t->timestamps();
        });
        if (Schema::hasTable('pages')) return;
        Schema::create('pages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('site_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->string('path');                     // 例: "/", "/about"
            $t->unsignedInteger('sort')->default(1);
            $t->longText('published_html')->nullable();
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
            $t->unique(['site_id','path']);
        });
        if (Schema::hasTable('blocks')) return;
        Schema::create('blocks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('page_id')->constrained()->cascadeOnDelete();
            $t->string('type');                     // "hero" | "features" | "cta" など
            $t->json('data');                       // ブロックごとの内容
            $t->unsignedInteger('sort')->default(1);
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocks');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('sites');
    }
};
