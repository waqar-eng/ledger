<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ledgers', function (Blueprint $table) {
            $table->foreignId('ledger_season_id')
                ->after('category_id')
                      ->default(1)
                ->constrained('ledger_seasons')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ledgers', function (Blueprint $table) {
            $table->dropForeign(['ledger_season_id']);
            $table->dropColumn('ledger_season_id');
        });
    }
};
