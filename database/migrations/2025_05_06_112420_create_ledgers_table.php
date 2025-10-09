<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ledgers', function (Blueprint $table) {
            $table->id();
            $table->string('description', 255);
            $table->string('bill_no', 255)->nullable();
            $table->decimal('amount', 10, 2);
            $table->enum('type', ['credit', 'debit']);
            $table->date('date')->default(now());
            $table->enum('ledger_type', ['sale', 'purchase', 'expense', 'investment', 'withdraw' ,'repayment','moisture_loss' ,'amount_received','other']);
            $table->decimal('total_amount', 15, 2)->nullable();
            $table->enum('payment_type', ['cash', 'credit', 'partial'])->nullable();
            $table->enum('payment_method', ['cash', 'bank'])->nullable();
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('remaining_amount', 10, 2)->default(0);
            $table->string('quantity')->nullable();
            $table->decimal('rate', 10, 2)->nullable();
            $table->foreignId(column: 'customer_id')->nullable();
            $table->foreignId(column: 'user_id')->nullable();         
            $table->foreignId(column: 'category_id')->nullable();
            $table->softDeletes();     
            $table->timestamps();
        });
        
        
        
    }

    public function down(): void
    {
        Schema::dropIfExists('ledgers');
    }
};
