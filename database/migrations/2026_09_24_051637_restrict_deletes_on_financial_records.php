<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Financial records must never disappear as a side effect of deleting a company, account, party or header.
 * These foreign keys previously cascaded deletes; they now refuse the delete instead.
 */
return new class extends Migration
{
    /**
     * @var array<string, array<string, string>> table => [column => referenced table]
     */
    protected const RESTRICTED_FOREIGN_KEYS = [
        'accounts' => ['company_id' => 'companies'],
        'account_balances' => ['company_id' => 'companies', 'account_id' => 'accounts'],
        'attachments' => ['company_id' => 'companies'],
        'audit_logs' => ['company_id' => 'companies'],
        'bank_accounts' => ['company_id' => 'companies', 'currency_id' => 'currencies'],
        'bank_reconciliations' => ['company_id' => 'companies', 'bank_account_id' => 'bank_accounts'],
        'bank_transactions' => ['bank_account_id' => 'bank_accounts'],
        'budgets' => ['company_id' => 'companies', 'fiscal_year_id' => 'fiscal_years'],
        'budget_lines' => ['company_id' => 'companies', 'account_id' => 'accounts'],
        'cash_accounts' => ['company_id' => 'companies', 'gl_account_id' => 'accounts'],
        'cost_centers' => ['company_id' => 'companies'],
        'customers' => ['company_id' => 'companies'],
        'customer_credit_notes' => ['company_id' => 'companies', 'customer_id' => 'customers'],
        'customer_debit_notes' => ['company_id' => 'companies', 'customer_id' => 'customers', 'customer_invoice_id' => 'customer_invoices'],
        'customer_invoices' => ['company_id' => 'companies', 'customer_id' => 'customers'],
        'customer_invoice_lines' => ['account_id' => 'accounts'],
        'customer_receipts' => ['company_id' => 'companies', 'customer_id' => 'customers'],
        'exchange_rates' => ['company_id' => 'companies', 'currency_id' => 'currencies'],
        'fiscal_periods' => ['fiscal_year_id' => 'fiscal_years'],
        'fiscal_years' => ['company_id' => 'companies'],
        'journals' => ['company_id' => 'companies'],
        'journal_lines' => ['journal_id' => 'journals', 'account_id' => 'accounts'],
        'number_sequences' => ['company_id' => 'companies'],
        'payment_allocations' => ['supplier_payment_id' => 'supplier_payments', 'supplier_invoice_id' => 'supplier_invoices'],
        'payment_methods' => ['company_id' => 'companies'],
        'receipt_allocations' => ['customer_receipt_id' => 'customer_receipts', 'customer_invoice_id' => 'customer_invoices'],
        'recurring_journals' => ['company_id' => 'companies'],
        'suppliers' => ['company_id' => 'companies'],
        'supplier_credit_notes' => ['company_id' => 'companies', 'supplier_id' => 'suppliers', 'supplier_invoice_id' => 'supplier_invoices'],
        'supplier_debit_notes' => ['company_id' => 'companies', 'supplier_id' => 'suppliers'],
        'supplier_invoices' => ['company_id' => 'companies', 'supplier_id' => 'suppliers'],
        'supplier_invoice_lines' => ['account_id' => 'accounts'],
        'supplier_payments' => ['company_id' => 'companies', 'supplier_id' => 'suppliers'],
        'taxes' => ['company_id' => 'companies'],
        'tax_transactions' => ['company_id' => 'companies', 'tax_id' => 'taxes', 'invoice_id' => 'supplier_invoices', 'payment_id' => 'supplier_payments'],
    ];

    public function up(): void
    {
        $this->replaceForeignKeys(fn ($foreignKey) => $foreignKey->restrictOnDelete());
    }

    public function down(): void
    {
        $this->replaceForeignKeys(fn ($foreignKey) => $foreignKey->cascadeOnDelete());
    }

    protected function replaceForeignKeys(Closure $onDelete): void
    {
        foreach (self::RESTRICTED_FOREIGN_KEYS as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns, $onDelete) {
                foreach ($columns as $column => $referencedTable) {
                    $blueprint->dropForeign([$column]);
                    $onDelete($blueprint->foreign($column)->references('id')->on($referencedTable));
                }
            });
        }
    }
};
