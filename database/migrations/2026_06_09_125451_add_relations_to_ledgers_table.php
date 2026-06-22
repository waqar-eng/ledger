<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ledgers', function (Blueprint $table) {

            $table->foreignId('from_account_id')
                ->nullable()
                ->after('category_id')
                ->constrained('accounts')
                ->nullOnDelete();

            $table->foreignId('to_account_id')
                ->nullable()
                ->after('from_account_id')
                ->constrained('accounts')
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ledgers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('from_account_id');
            $table->dropConstrainedForeignId('to_account_id');
            $table->dropConstrainedForeignId('created_by');
        });
    }
};