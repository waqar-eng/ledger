<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('season_id')
                ->nullable()
                ->after('id')
                ->constrained('ledger_seasons')
                ->nullOnDelete();
        });

        // Categories
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('season_id')
                ->nullable()
                ->after('id')
                ->constrained('ledger_seasons')
                ->nullOnDelete();
        });

        // Expense Types
        Schema::table('expense_types', function (Blueprint $table) {
            $table->foreignId('season_id')
                ->nullable()
                ->after('id')
                ->constrained('ledger_seasons')
                ->nullOnDelete();
        });

        // Roles
        Schema::table('roles', function (Blueprint $table) {
            $table->foreignId('season_id')
                ->nullable()
                ->after('id')
                ->constrained('ledger_seasons')
                ->nullOnDelete();
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->foreignId('season_id')
                ->nullable()
                ->constrained('ledger_seasons')
                ->nullOnDelete();
        });

        Schema::table('account_payables', function (Blueprint $table) {
            $table->unsignedBigInteger('season_id')->nullable()->after('id')->nullOnDelete();
        });

        Schema::table('account_receivables', function (Blueprint $table) {
            $table->unsignedBigInteger('season_id')->nullable()->after('id')->nullOnDelete();
        });
        
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['season_id']);
            $table->dropColumn('season_id');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['season_id']);
            $table->dropColumn('season_id');
        });

        Schema::table('expense_types', function (Blueprint $table) {
            $table->dropForeign(['season_id']);
            $table->dropColumn('season_id');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['season_id']);
            $table->dropColumn('season_id');
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->dropForeign(['season_id']);
            $table->dropColumn('season_id');
        });

        Schema::table('account_payables', function (Blueprint $table) {
            $table->dropColumn('season_id');
        });

        Schema::table('account_receivables', function (Blueprint $table) {
            $table->dropColumn('season_id');
        });
    }
};