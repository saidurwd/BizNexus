<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('quotation_number', 50);
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('customer_reference', 100)->nullable();
            $table->date('quotation_date');
            $table->date('valid_until')->nullable();
            $table->foreignId('currency_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('total_amount', 20, 4)->default(0);
            $table->string('status', 30)->default('DRAFT');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'quotation_number']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('sales_quotation_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('description', 500);
            $table->decimal('quantity', 20, 4);
            $table->decimal('unit_price', 20, 4);
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->foreignId('tax_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('total_amount', 20, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('order_number', 50);
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('sales_quotation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_reference', 100)->nullable();
            $table->date('order_date');
            $table->date('delivery_date')->nullable();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('currency_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('total_amount', 20, 4)->default(0);
            $table->string('status', 30)->default('DRAFT');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'order_number']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('sales_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('description', 500);
            $table->decimal('quantity', 20, 4);
            $table->decimal('unit_price', 20, 4);
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->foreignId('tax_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('subtotal', 20, 4)->default(0);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('total_amount', 20, 4)->default(0);
            $table->decimal('delivered_quantity', 20, 4)->default(0);
            $table->decimal('delivered_cost_value', 20, 4)->default(0);
            $table->decimal('invoiced_quantity', 20, 4)->default(0);
            $table->decimal('invoiced_cost_value', 20, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('delivery_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('delivery_number', 50);
            $table->foreignId('sales_order_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->date('delivery_date');
            $table->string('carrier', 100)->nullable();
            $table->string('tracking_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('POSTED');
            $table->foreignId('journal_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'delivery_number']);
        });

        Schema::create('delivery_note_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_order_line_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 20, 4);
            $table->decimal('cost_value', 20, 4)->default(0);
            $table->timestamps();
        });

        Schema::table('customer_invoices', function (Blueprint $table) {
            $table->foreignId('sales_order_id')->nullable()->after('customer_id')->constrained()->restrictOnDelete();
        });

        Schema::table('customer_invoice_lines', function (Blueprint $table) {
            $table->foreignId('sales_order_line_id')->nullable()->after('customer_invoice_id')->constrained()->restrictOnDelete();
        });

        Schema::table('customer_credit_note_lines', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('customer_credit_note_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->nullable()->after('product_id')->constrained()->restrictOnDelete();
            $table->decimal('cost_value', 20, 4)->nullable()->after('total_amount');
        });

        Schema::table('customer_credit_notes', function (Blueprint $table) {
            $table->foreignId('cost_journal_id')->nullable()->after('journal_id')->constrained('journals')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customer_credit_notes', fn (Blueprint $table) => $table->dropConstrainedForeignId('cost_journal_id'));
        Schema::table('customer_credit_note_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_id');
            $table->dropConstrainedForeignId('product_id');
            $table->dropColumn('cost_value');
        });
        Schema::table('customer_invoice_lines', fn (Blueprint $table) => $table->dropConstrainedForeignId('sales_order_line_id'));
        Schema::table('customer_invoices', fn (Blueprint $table) => $table->dropConstrainedForeignId('sales_order_id'));

        foreach (['delivery_note_lines', 'delivery_notes', 'sales_order_lines', 'sales_orders', 'sales_quotation_lines', 'sales_quotations'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
