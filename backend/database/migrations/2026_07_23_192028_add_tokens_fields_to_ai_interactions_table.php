<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_interactions', function (Blueprint $table) {

            $table->integer('cached_tokens')->default(0)->after('reasoning_tokens');

            $table->integer('total_tokens')->default(0)->after('cached_tokens');

        });
    }

    public function down(): void
    {
        Schema::table('ai_interactions', function (Blueprint $table) {

            $table->dropColumn([
                'cached_tokens',
                'total_tokens',
            ]);

        });
    }
};
