<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipt_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_invoice_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 20, 4)->default(0);
            $table->timestamps();
            
            $table->index(['customer_receipt_id', 'customer_invoice_id'], 'receipt_alloc_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_allocations');
    }
};
