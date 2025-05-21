<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ledgers', function (Blueprint $table) {
            $table->enum('ledger_type', ['sale', 'purchase', 'expense'])->after('type');
            $table->decimal('total_amount', 15, 2)->nullable(); // New column for running balance

        });
    }

    public function down(): void
    {
        Schema::table('ledgers', function (Blueprint $table) {
            $table->dropColumn('ledger_type');
        });
    }
};
