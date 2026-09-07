<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('tax_code', 50);
            $table->string('tax_name', 255);
            $table->enum('tax_type', ['VAT', 'WITHHOLDING_TAX', 'INCOME_TAX', 'OTHER']);
            $table->decimal('rate', 10, 4)->default(0);
            $table->boolean('is_inclusive')->default(false);
            $table->foreignId('input_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('output_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('status', 30)->default('active');
            $table->timestamps();
            
            $table->unique(['company_id', 'tax_code']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxes');
    }
};
