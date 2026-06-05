<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('season_summaries', function (Blueprint $table) {
            $table->foreignId('total_withdraw')
                ->after('total_investment')
                      ->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('season_summaries', function (Blueprint $table) {
            $table->dropColumn('total_withdraw');
        });
    }
};
