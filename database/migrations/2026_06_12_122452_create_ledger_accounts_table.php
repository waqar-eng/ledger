<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ledger_accounts', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('ledger_id');
            $table->unsignedBigInteger('account_id');

            // amount allocated to this account
            $table->decimal('amount', 15, 2)->default(0);

            $table->timestamps();

            // Foreign keys
            $table->foreign('ledger_id')
                ->references('id')
                ->on('ledgers')
                ->onDelete('cascade');

            $table->foreign('account_id')
                ->references('id')
                ->on('accounts')
                ->onDelete('restrict');

            // prevent duplicate account per ledger
            $table->unique(['ledger_id', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_accounts');
    }
};