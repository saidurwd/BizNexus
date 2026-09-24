<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Workflow entity types and the tables holding their documents, used to backfill the company.
     */
    protected const ENTITY_TABLES = [
        'supplier_invoice' => 'supplier_invoices',
        'customer_invoice' => 'customer_invoices',
        'supplier_payment' => 'supplier_payments',
        'customer_receipt' => 'customer_receipts',
    ];

    public function up(): void
    {
        Schema::table('workflow_instances', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->restrictOnDelete();
        });

        foreach (self::ENTITY_TABLES as $entityType => $documentTable) {
            DB::table('workflow_instances')->where('entity_type', $entityType)->update([
                'company_id' => DB::raw("(SELECT {$documentTable}.company_id FROM {$documentTable} WHERE {$documentTable}.id = workflow_instances.entity_id)"),
            ]);
        }

        $unresolved = DB::table('workflow_instances')->whereNull('company_id')->count();

        if ($unresolved > 0) {
            throw new RuntimeException("{$unresolved} workflow instances have an entity type without a known company; map it in ENTITY_TABLES first.");
        }

        Schema::table('workflow_instances', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable(false)->change();
            $table->index(['company_id', 'current_state']);
        });
    }

    public function down(): void
    {
        Schema::table('workflow_instances', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'current_state']);
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
