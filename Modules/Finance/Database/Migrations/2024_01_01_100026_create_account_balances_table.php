<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('fiscal_period_id')->nullable()->constrained('fiscal_periods')->nullOnDelete();
            $table->date('balance_date');
            $table->decimal('opening_balance', 20, 4)->default(0);
            $table->decimal('period_debit', 20, 4)->default(0);
            $table->decimal('period_credit', 20, 4)->default(0);
            $table->decimal('closing_balance', 20, 4)->default(0);
            $table->enum('balance_type', ['DEBIT', 'CREDIT'])->default('DEBIT');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'account_id', 'balance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_balances');
    }
};
