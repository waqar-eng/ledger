<?php

use App\AppEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ledgers', function (Blueprint $table) {
            $table->id();
            $table->string('description', 255);
            $table->string('reference', 255)->nullable();
            $table->decimal(AppEnum::Amount, 10, 2);
            $table->enum('type', [AppEnum::Credit, AppEnum::Debit]);
            $table->date('date')->default(now());
            $table->enum('ledger_type', [AppEnum::Sale, AppEnum::Purchase, AppEnum::Expense, AppEnum::Amount]);
            $table->decimal('total_amount', 15, 2)->nullable();
            $table->foreignId(column: 'customer_id')->constrained('customers')
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
