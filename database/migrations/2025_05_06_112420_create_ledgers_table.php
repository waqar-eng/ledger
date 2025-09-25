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
            $table->enum('ledger_type', ['sale', 'purchase', 'expense', 'investment', 'withdraw' ,'repayment' ,'other']);
            $table->decimal('total_amount', 15, 2)->nullable();
            $table->enum('payment_type', ['cash', 'loan', 'mix'])->nullable();
            $table->enum('payment_method', ['cash', 'bank'])->nullable();
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('remaining_amount', 10, 2)->default(0);
            $table->string('quantity')->nullable();
            $table->decimal('rate', 10, 2)->nullable();
            $table->foreignId(column: 'customer_id')->constrained('customers')
            ->onDelete('cascade')->nullable();
           
            $table->foreignId(column: 'user_id')->constrained('users')
            ->onDelete('cascade')->nullable();

            $table->softDeletes();     
            $table->timestamps();
        });
        
        
        
    }

    public function down(): void
    {
        Schema::dropIfExists('ledgers');
    }
};
