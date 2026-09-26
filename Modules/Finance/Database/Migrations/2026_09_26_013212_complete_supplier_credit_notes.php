<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Supplier credit notes become purchase documents with lines, tax codes and currency, mirroring supplier
 * invoices. Statuses move from a lowercase enum (which never matched the model's constants) to uppercase
 * strings, and numbers become unique per company instead of across all companies.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_credit_notes', function (Blueprint $table) {
            $table->string('status', 30)->default('DRAFT')->change();
        });

        DB::table('supplier_credit_notes')->update(['status' => DB::raw('UPPER(status)')]);

        Schema::table('supplier_credit_notes', function (Blueprint $table) {
            $table->dropUnique(['credit_note_number']);
            $table->unique(['company_id', 'credit_note_number'], 'supplier_credit_notes_company_number_unique');
            $table->foreignId('currency_id')->nullable()->after('credit_note_date')->constrained()->restrictOnDelete();
            $table->decimal('exchange_rate', 20, 8)->default(1)->after('currency_id');
            $table->decimal('discount_amount', 20, 4)->default(0)->after('tax_amount');
            $table->decimal('applied_amount', 20, 4)->default(0)->after('total_amount');
            $table->text('rejection_reason')->nullable()->after('status');
            $table->unsignedBigInteger('journal_id')->nullable()->after('rejection_reason');
            $table->foreignId('approved_by')->nullable()->after('updated_by')->constrained('users')->nullOnDelete();
        });

        Schema::create('supplier_credit_note_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_credit_note_id')->constrained()->cascadeOnDelete();
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
            $table->foreignId('supplier_credit_note_id')->nullable()->after('customer_credit_note_id')->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tax_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_credit_note_id');
        });

        Schema::dropIfExists('supplier_credit_note_lines');

        Schema::table('supplier_credit_notes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('currency_id');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['exchange_rate', 'discount_amount', 'applied_amount', 'rejection_reason', 'journal_id']);
            $table->dropUnique('supplier_credit_notes_company_number_unique');
            $table->unique('credit_note_number');
        });

        DB::table('supplier_credit_notes')->update(['status' => DB::raw('LOWER(status)')]);
    }
};
