<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer credit notes become full sales documents: currency, lines with tax codes, the invoice they credit
 * and how much of them was applied to it. The table was never used, so the single "amount" column goes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_credit_notes', function (Blueprint $table) {
            $table->foreignId('customer_invoice_id')->nullable()->after('customer_id')->constrained('customer_invoices')->restrictOnDelete();
            $table->foreignId('currency_id')->nullable()->after('note_date')->constrained()->restrictOnDelete();
            $table->decimal('exchange_rate', 20, 8)->default(1)->after('currency_id');
            $table->decimal('subtotal', 20, 4)->default(0)->after('description');
            $table->decimal('tax_amount', 20, 4)->default(0)->after('subtotal');
            $table->decimal('discount_amount', 20, 4)->default(0)->after('tax_amount');
            $table->decimal('total_amount', 20, 4)->default(0)->after('discount_amount');
            $table->decimal('applied_amount', 20, 4)->default(0)->after('total_amount');
            $table->text('rejection_reason')->nullable()->after('status');
            $table->timestamp('posted_at')->nullable()->after('journal_id');
            $table->foreignId('approved_by')->nullable()->after('updated_by')->constrained('users')->nullOnDelete();
            $table->dropColumn(['amount', 'reference_type', 'reference_id']);
        });

        Schema::create('customer_credit_note_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_credit_note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            $table->string('description');
            $table->decimal('quantity', 20, 4)->default(1);
            $table->decimal('unit_price', 20, 4)->default(0);
            $table->decimal('subtotal', 20, 4)->default(0);
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->restrictOnDelete();
            $table->string('supply_type', 30)->nullable();
            $table->boolean('is_reverse_charge')->default(false);
            $table->decimal('tax_amount', 20, 4)->default(0);
            $table->decimal('discount_amount', 20, 4)->default(0);
            $table->decimal('total_amount', 20, 4)->default(0);
            $table->timestamps();
        });

        Schema::table('tax_transactions', function (Blueprint $table) {
            $table->foreignId('customer_credit_note_id')->nullable()->after('customer_invoice_id')->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tax_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_credit_note_id');
        });

        Schema::dropIfExists('customer_credit_note_lines');

        Schema::table('customer_credit_notes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_invoice_id');
            $table->dropConstrainedForeignId('currency_id');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['exchange_rate', 'subtotal', 'tax_amount', 'discount_amount', 'total_amount', 'applied_amount', 'rejection_reason', 'posted_at']);
            $table->decimal('amount', 20, 4)->default(0);
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
        });
    }
};
