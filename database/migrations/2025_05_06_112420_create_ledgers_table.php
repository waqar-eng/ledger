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
            $table->enum('type', ['credit', 'debit']);
            $table->date('date')->default(now());
            $table->enum('ledger_type', ['sale', 'purchase', 'expense', 'investment', 'withdraw' ,'receive-payment','payment' ,'moisture_loss','other']);
            $table->decimal('amount', 15, 2)->nullable();
            $table->decimal('total_amount', 15, 2)->nullable();
            $table->enum('payment_method', ['cash', 'bank'])->nullable();
            $table->foreignId( 'user_id')->nullable();
            $table->foreignId( 'category_id');

            $table->foreignId('parent_id')->nullable()
            ->constrained('ledgers')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();
        });



    }

    public function down(): void
    {
        Schema::dropIfExists('ledgers');
    }
};
