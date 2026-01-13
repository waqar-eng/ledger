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
        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId(column: 'user_id')->constrained('users')
            ->onDelete('cascade')->nullable();

            $table->foreignId(column: 'ledger_id')->constrained('ledgers')
            ->onDelete('cascade')->nullable();

            $table->foreignId(column: 'category_id')->nullable()->constrained('categories')
            ->onDelete('cascade');

            $table->enum('type', ['investment', 'withdraw']);
            $table->integer('amount');
            $table->decimal('total_amount', 15, 2)->nullable();
            $table->date('date')->default(now());

            $table->foreignId('parent_id')->nullable()
            ->constrained('investments')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investments');
    }
};
