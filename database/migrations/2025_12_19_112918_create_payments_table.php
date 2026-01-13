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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ledger_id')->constrained('ledgers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->nullable();
            $table->foreignId( 'category_id')->constrained('categories')->onDelete('cascade')->nullable();
            $table->decimal('amount', 15, 2);
            $table->decimal('paid_amount', 15, 2);
            $table->decimal('remaining_amount', 15, 2);
            $table->enum('direction', ['receive', 'pay']);
            $table->foreignId('parent_id')->nullable()
            ->constrained('payments')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
