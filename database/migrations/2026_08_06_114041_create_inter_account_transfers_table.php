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
        Schema::create('inter_account_transfers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ledger_id')
                ->constrained('ledgers')
                ->cascadeOnDelete();

            $table->foreignId('from_account_id')
                ->constrained('accounts')
                ->cascadeOnDelete();

            $table->foreignId('to_account_id')
                ->constrained('accounts')
                ->cascadeOnDelete();

            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();

            $table->decimal('amount', 15, 2);

            $table->date('date');

            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inter_account_transfers');
    }
};
