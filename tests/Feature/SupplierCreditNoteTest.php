<?php

use Modules\Core\Models\Company;
use Modules\Core\Models\FiscalPeriod;
use Modules\Core\Models\FiscalYear;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\JournalLine;
use Modules\Finance\Models\Supplier;
use Modules\Finance\Models\SupplierCreditNote;
use Modules\Finance\Models\Tax;
use Modules\Finance\Models\TaxTransaction;
use Modules\Finance\Services\SupplierCreditNoteService;
use Modules\Finance\Services\SupplierInvoiceService;

beforeEach(function () {
    $this->company = Company::factory()->create();
    app(CompanyContextService::class)->pinCompany($this->company->id);
    $year = FiscalYear::create(['company_id' => $this->company->id, 'name' => 'FY', 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(), 'status' => 'OPEN']);
    FiscalPeriod::create(['fiscal_year_id' => $year->id, 'period_name' => 'Y', 'period_number' => 1, 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(), 'status' => 'OPEN']);

    $this->expense = Account::factory()->expense()->create(['company_id' => $this->company->id]);
    $this->payable = Account::factory()->liability()->create(['company_id' => $this->company->id]);
    $this->inputVat = Account::factory()->asset()->create(['company_id' => $this->company->id]);
    $this->vat = Tax::factory()->create(['company_id' => $this->company->id, 'tax_code' => 'VAT15', 'rate' => 15, 'is_inclusive' => false, 'is_group' => false, 'is_recoverable' => true, 'input_account_id' => $this->inputVat->id]);
    $this->supplier = Supplier::factory()->create(['company_id' => $this->company->id, 'payable_account_id' => $this->payable->id]);
    $this->clerk = companyUser(['finance.supplier-credit-notes.view', 'finance.supplier-credit-notes.create', 'finance.supplier-credit-notes.update'], $this->company);
    $this->approver = companyUser([], $this->company);

    $invoices = app(SupplierInvoiceService::class);
    $this->actingAs($this->clerk);
    $invoice = $invoices->submitInvoice($invoices->createInvoice([
        'company_id' => $this->company->id, 'supplier_id' => $this->supplier->id,
        'invoice_date' => now()->toDateString(), 'due_date' => now()->addMonth()->toDateString(),
        'lines' => [['account_id' => $this->expense->id, 'description' => 'Laptops', 'quantity' => 2, 'unit_price' => 100, 'tax_id' => $this->vat->id]],
    ]));
    $this->actingAs($this->approver);
    $this->invoice = $invoices->postInvoice($invoices->approveInvoice($invoice));
});

function supplierLedgerBalance(int $accountId): string
{
    return (string) JournalLine::where('account_id', $accountId)->get()
        ->reduce(fn ($total, $line) => bcadd($total, bcsub($line->debit, $line->credit, 4), 4), '0');
}

test('posting a supplier credit note reverses cost and input tax and reduces what is owed', function () {
    $service = app(SupplierCreditNoteService::class);
    $this->actingAs($this->clerk);
    $creditNote = $service->submit($service->create([
        'company_id' => $this->company->id, 'supplier_id' => $this->supplier->id, 'supplier_invoice_id' => $this->invoice->id,
        'credit_note_date' => now()->toDateString(), 'reason' => 'One laptop returned',
        'lines' => [['account_id' => $this->expense->id, 'description' => 'Laptop returned', 'quantity' => 1, 'unit_price' => 100, 'tax_id' => $this->vat->id]],
    ]));
    $this->actingAs($this->approver);
    $creditNote = $service->post($service->approve($creditNote));

    expect(supplierLedgerBalance($this->expense->id))->toBe('100.0000')
        ->and(supplierLedgerBalance($this->inputVat->id))->toBe('15.0000')
        ->and(supplierLedgerBalance($this->payable->id))->toBe('-115.0000')
        ->and($creditNote->status)->toBe(SupplierCreditNote::STATUS_POSTED)
        ->and($creditNote->credit_note_number)->toStartWith('SCN-')
        ->and((string) $this->invoice->fresh()->outstanding_amount)->toBe('115.0000')
        ->and((string) TaxTransaction::where('supplier_credit_note_id', $creditNote->id)->value('tax_amount'))->toBe('-15.0000');
});

test('supplier credit notes are recorded on screen from the invoice they credit', function () {
    actingInCompany($this->clerk, $this->company)
        ->get(route('finance.supplier-credit-notes.create', ['invoice' => $this->invoice->id]))
        ->assertOk()
        ->assertSee('Laptops');

    actingInCompany($this->clerk, $this->company)
        ->post(route('finance.supplier-credit-notes.store'), [
            'supplier_id' => $this->supplier->id, 'supplier_invoice_id' => $this->invoice->id,
            'credit_note_date' => now()->toDateString(), 'reason' => 'Price correction',
            'lines' => [['account_id' => $this->expense->id, 'description' => 'Rebate', 'quantity' => 1, 'unit_price' => 40, 'tax_id' => $this->vat->id]],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $creditNote = SupplierCreditNote::sole();

    expect((string) $creditNote->total_amount)->toBe('46.0000');

    actingInCompany($this->clerk, $this->company)->get(route('finance.supplier-credit-notes.index'))->assertOk()->assertSee($creditNote->credit_note_number);
    actingInCompany($this->clerk, $this->company)->get(route('finance.supplier-credit-notes.edit', $creditNote->id))->assertOk()->assertSee('Rebate');
    actingInCompany($this->clerk, $this->company)->get(route('finance.supplier-credit-notes.show', $creditNote->id))->assertOk()->assertSee('Price correction');
});
