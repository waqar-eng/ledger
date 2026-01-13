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
        Schema::create('season_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained('ledger_seasons')->onDelete('cascade');
            $table->decimal('total_sales', 18, 2)->default(0);
            $table->decimal('total_purchases', 18, 2)->default(0);
            $table->decimal('total_expenses', 18, 2)->default(0);
            $table->decimal('total_investment', 18, 2)->default(0);
            $table->decimal('profit', 18, 2)->default(0);
            $table->json('remaining_stock')->nullable();
            $table->json('total_payables')->nullable();
            $table->json('total_receivables')->nullable();
            $table->text('cannot_close_reason')->nullable();
            $table->json('investor_breakdown')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('season_summaries');
    }
};
