<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained()->cascadeOnDelete();
            $table->string('transaction_number', 50);
            $table->date('transaction_date');
            $table->enum('transaction_type', ['DEPOSIT', 'WITHDRAWAL', 'TRANSFER', 'CHARGE', 'INTEREST']);
            $table->decimal('amount', 20, 4)->default(0);
            $table->string('reference', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('status', 30)->default('COMPLETED');
            $table->unsignedBigInteger('journal_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->unique(['bank_account_id', 'transaction_number']);
            $table->index('status');
            $table->index('transaction_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
