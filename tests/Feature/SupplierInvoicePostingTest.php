<?php

use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Models\Company;
use Modules\Core\Models\FiscalPeriod;
use Modules\Core\Models\FiscalYear;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\Journal;
use Modules\Finance\Models\Supplier;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Finance\Services\SupplierInvoiceService;

beforeEach(function () {
    $this->company = Company::factory()->create();
    app(CompanyContextService::class)->pinCompany($this->company->id);

    $fiscalYear = FiscalYear::create([
        'company_id' => $this->company->id, 'name' => 'FY', 'status' => 'OPEN',
        'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(),
    ]);
    FiscalPeriod::create([
        'fiscal_year_id' => $fiscalYear->id, 'period_name' => 'Year', 'period_number' => 1, 'status' => 'OPEN',
        'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(),
    ]);

    $payable = Account::factory()->liability()->create(['company_id' => $this->company->id]);
    $this->expense = Account::factory()->expense()->create(['company_id' => $this->company->id]);
    $this->supplier = Supplier::factory()->create(['company_id' => $this->company->id, 'payable_account_id' => $payable->id]);
    $this->invoices = app(SupplierInvoiceService::class);

    $this->clerk = companyUser([], $this->company);
    $this->approver = companyUser([], $this->company);
});

function submittedInvoice(): SupplierInvoice
{
    test()->actingAs(test()->clerk);

    $invoice = test()->invoices->createInvoice([
        'company_id' => test()->company->id,
        'supplier_id' => test()->supplier->id,
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addDays(30)->toDateString(),
        'lines' => [['account_id' => test()->expense->id, 'description' => 'Consulting', 'quantity' => 2, 'unit_price' => 250]],
    ]);

    return test()->invoices->submitInvoice($invoice);
}

test('the clerk who created an invoice cannot approve it', function () {
    $this->invoices->approveInvoice(submittedInvoice());
})->throws(InvalidAccountingTransactionException::class, 'You cannot approve a supplier invoice you created.');

test('approving and posting an invoice creates exactly one posted journal linked to it', function () {
    $invoice = submittedInvoice();
    $this->actingAs($this->approver);

    $posted = $this->invoices->postInvoice($this->invoices->approveInvoice($invoice));

    $journals = Journal::where('reference_type', 'supplier_invoice')->where('reference_id', $invoice->id)->get();
    expect($posted->status)->toBe(SupplierInvoice::STATUS_POSTED)
        ->and(Journal::count())->toBe(1)
        ->and($journals)->toHaveCount(1)
        ->and($journals->first()->status)->toBe(Journal::STATUS_POSTED)
        ->and($journals->first()->id)->toBe($posted->journal_id)
        ->and($journals->first()->total_credit)->toEqual(500);
});

test('an invoice cannot be posted twice', function () {
    $invoice = submittedInvoice();
    $this->actingAs($this->approver);
    $approved = $this->invoices->approveInvoice($invoice);
    $this->invoices->postInvoice($approved);

    expect(fn () => $this->invoices->postInvoice($approved))->toThrow(InvalidAccountingTransactionException::class, 'Only approved invoices can be posted')
        ->and(Journal::count())->toBe(1);
});
