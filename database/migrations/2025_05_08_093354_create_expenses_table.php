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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ledger_id')->constrained('ledgers')->onDelete('cascade');
            $table->decimal('amount', 15, 2)->nullable();
            $table->decimal('loss_quantity', 10, 2)->nullable();
            $table->decimal('rate', 10, 2)->nullable();
            $table->foreignId('parent_id')->nullable()
            ->constrained('expenses')->nullOnDelete();
            $table->foreignId('expense_type_id')->constrained('expense_types')->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }


};
