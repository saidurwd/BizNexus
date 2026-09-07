<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->string('payment_number', 50);
            $table->date('payment_date');
            $table->foreignId('currency_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('exchange_rate', 20, 8)->default(1);
            $table->decimal('amount', 20, 4)->default(0);
            $table->string('payment_method', 50)->default('BANK_TRANSFER');
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('status', 30)->default('DRAFT');
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->unique(['company_id', 'payment_number']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
    }
};
