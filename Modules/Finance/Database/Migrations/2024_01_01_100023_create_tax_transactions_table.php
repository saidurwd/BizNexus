<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('tax_id')->constrained('taxes')->cascadeOnDelete();
            $table->enum('transaction_type', ['INPUT', 'OUTPUT', 'WITHHOLDING', 'ADJUSTMENT'])->default('INPUT');
            $table->foreignId('journal_line_id')->nullable()->constrained('journal_lines')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('supplier_invoices')->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('supplier_payments')->cascadeOnDelete();
            $table->decimal('taxable_amount', 20, 4);
            $table->decimal('tax_amount', 20, 4);
            $table->decimal('exchange_rate', 20, 6)->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->date('tax_date');
            $table->string('reference_number')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'tax_id', 'tax_date']);
            $table->index(['company_id', 'transaction_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_transactions');
    }
};
