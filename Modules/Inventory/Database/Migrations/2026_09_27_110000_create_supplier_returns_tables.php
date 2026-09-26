<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('return_number', 50);
            $table->foreignId('purchase_order_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->date('return_date');
            $table->string('reason', 255)->nullable();
            $table->string('status', 30)->default('POSTED');
            $table->foreignId('journal_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'return_number']);
        });

        Schema::create('supplier_return_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_line_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 20, 4);
            $table->decimal('receipt_value', 20, 4)->default(0);
            $table->decimal('stock_value', 20, 4)->default(0);
            $table->timestamps();
        });

        Schema::table('supplier_credit_notes', function (Blueprint $table) {
            $table->foreignId('purchase_order_id')->nullable()->after('supplier_invoice_id')->constrained()->restrictOnDelete();
        });

        Schema::table('supplier_credit_note_lines', function (Blueprint $table) {
            $table->foreignId('purchase_order_line_id')->nullable()->after('supplier_credit_note_id')->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('supplier_credit_note_lines', fn (Blueprint $table) => $table->dropConstrainedForeignId('purchase_order_line_id'));
        Schema::table('supplier_credit_notes', fn (Blueprint $table) => $table->dropConstrainedForeignId('purchase_order_id'));
        Schema::dropIfExists('supplier_return_lines');
        Schema::dropIfExists('supplier_returns');
    }
};
