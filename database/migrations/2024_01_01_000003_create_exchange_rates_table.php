<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('currency_id')->constrained()->cascadeOnDelete();
            $table->date('rate_date');
            $table->decimal('exchange_rate', 20, 8);
            $table->string('source', 100)->nullable();
            $table->string('status', 30)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index(['company_id', 'currency_id', 'rate_date']);
            $table->index('rate_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
