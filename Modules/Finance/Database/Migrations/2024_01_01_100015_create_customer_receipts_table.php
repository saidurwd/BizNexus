<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('receipt_number', 50);
            $table->date('receipt_date');
            $table->foreignId('currency_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('exchange_rate', 20, 8)->default(1);
            $table->decimal('amount', 20, 4)->default(0);
            $table->string('receipt_method', 50)->default('BANK_TRANSFER');
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('status', 30)->default('DRAFT');
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->unique(['company_id', 'receipt_number']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_receipts');
    }
};
