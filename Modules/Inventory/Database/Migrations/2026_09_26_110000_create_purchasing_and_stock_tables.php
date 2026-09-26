<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 20, 4)->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'warehouse_id']);
            $table->index(['company_id', 'warehouse_id']);
        });

        Schema::create('stock_moves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->date('move_date');
            $table->decimal('quantity', 20, 4);
            $table->decimal('unit_cost', 20, 6)->default(0);
            $table->decimal('value', 20, 4)->default(0);
            $table->decimal('quantity_after', 20, 4)->default(0);
            $table->decimal('value_after', 20, 4)->default(0);
            $table->string('source_type', 40);
            $table->unsignedBigInteger('source_id');
            $table->unsignedBigInteger('source_line_id')->nullable();
            $table->string('reference', 50)->nullable();
            $table->foreignId('journal_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'product_id', 'move_date']);
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('order_number', 50);
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->string('supplier_reference', 100)->nullable();
            $table->date('order_date');
            $table->date('expected_date')->nullable();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('currency_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('total_amount', 20, 4)->default(0);
            $table->string('status', 30)->default('DRAFT');
            $table->text('notes')->nullable();
            $table->string('rejection_reason', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'order_number']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('description', 500);
            $table->decimal('quantity', 20, 4);
            $table->decimal('unit_price', 20, 4);
            $table->foreignId('tax_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('total_amount', 20, 4)->default(0);
            $table->decimal('received_quantity', 20, 4)->default(0);
            $table->decimal('received_functional_value', 20, 4)->default(0);
            $table->decimal('invoiced_quantity', 20, 4)->default(0);
            $table->decimal('invoiced_functional_value', 20, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('receipt_number', 50);
            $table->foreignId('purchase_order_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->date('receipt_date');
            $table->string('delivery_note', 100)->nullable();
            $table->foreignId('currency_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('exchange_rate', 20, 8)->default(1);
            $table->string('status', 30)->default('POSTED');
            $table->text('notes')->nullable();
            $table->foreignId('journal_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'receipt_number']);
        });

        Schema::create('goods_receipt_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_line_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 20, 4);
            $table->decimal('unit_price', 20, 4);
            $table->decimal('value', 20, 4);
            $table->decimal('functional_value', 20, 4);
            $table->timestamps();
        });

        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('adjustment_number', 50);
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->date('adjustment_date');
            $table->string('reason', 30);
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('DRAFT');
            $table->foreignId('journal_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'adjustment_number']);
        });

        Schema::create('stock_adjustment_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_adjustment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('system_quantity', 20, 4)->nullable();
            $table->decimal('quantity', 20, 4);
            $table->decimal('unit_cost', 20, 6)->nullable();
            $table->decimal('value', 20, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('transfer_number', 50);
            $table->foreignId('from_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('to_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->date('transfer_date');
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('POSTED');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'transfer_number']);
        });

        Schema::create('stock_transfer_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 20, 4);
            $table->timestamps();
        });

        Schema::table('supplier_invoices', function (Blueprint $table) {
            $table->foreignId('purchase_order_id')->nullable()->after('supplier_id')->constrained()->restrictOnDelete();
        });

        Schema::table('supplier_invoice_lines', function (Blueprint $table) {
            $table->foreignId('purchase_order_line_id')->nullable()->after('supplier_invoice_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->nullable()->after('purchase_order_line_id')->constrained()->restrictOnDelete();
        });

        Schema::table('customer_invoice_lines', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('customer_invoice_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->nullable()->after('product_id')->constrained()->restrictOnDelete();
            $table->decimal('cost_value', 20, 4)->nullable()->after('total_amount');
        });

        Schema::table('customer_invoices', function (Blueprint $table) {
            $table->foreignId('cost_journal_id')->nullable()->after('journal_id')->constrained('journals')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customer_invoices', fn (Blueprint $table) => $table->dropConstrainedForeignId('cost_journal_id'));
        Schema::table('customer_invoice_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_id');
            $table->dropConstrainedForeignId('product_id');
            $table->dropColumn('cost_value');
        });
        Schema::table('supplier_invoice_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
            $table->dropConstrainedForeignId('purchase_order_line_id');
        });
        Schema::table('supplier_invoices', fn (Blueprint $table) => $table->dropConstrainedForeignId('purchase_order_id'));

        foreach (['stock_transfer_lines', 'stock_transfers', 'stock_adjustment_lines', 'stock_adjustments', 'goods_receipt_lines', 'goods_receipts', 'purchase_order_lines', 'purchase_orders', 'stock_moves', 'stock_balances'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
