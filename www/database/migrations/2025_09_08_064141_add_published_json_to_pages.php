<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $t) {
            if (!Schema::hasColumn('pages', 'published_json')) {
                $t->longText('published_json')->nullable();
            }
            // 注意: published_at は既にあるので触らない
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $t) {
            if (Schema::hasColumn('pages', 'published_json')) {
                $t->dropColumn('published_json');
            }
        });
    }
};
