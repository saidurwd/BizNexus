<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A supplier invoice number is the supplier's own document number, so two suppliers may both send "INV-001".
 * It is unique per supplier within a company, not across the company.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_invoices', function (Blueprint $table) {
            $table->unique(['company_id', 'supplier_id', 'invoice_number'], 'supplier_invoices_supplier_number_unique');
        });

        if (Schema::hasIndex('supplier_invoices', ['company_id', 'invoice_number'], 'unique')) {
            Schema::table('supplier_invoices', function (Blueprint $table) {
                $table->dropUnique(['company_id', 'invoice_number']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('supplier_invoices', function (Blueprint $table) {
            $table->unique(['company_id', 'invoice_number']);
            $table->dropUnique('supplier_invoices_supplier_number_unique');
        });
    }
};
