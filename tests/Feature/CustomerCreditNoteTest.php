<?php

use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Models\Company;
use Modules\Core\Models\FiscalPeriod;
use Modules\Core\Models\FiscalYear;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\Customer;
use Modules\Finance\Models\CustomerCreditNote;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Models\JournalLine;
use Modules\Finance\Models\Tax;
use Modules\Finance\Models\TaxTransaction;
use Modules\Finance\Services\CustomerCreditNoteService;
use Modules\Finance\Services\CustomerInvoiceService;

beforeEach(function () {
    $this->company = Company::factory()->create();
    app(CompanyContextService::class)->pinCompany($this->company->id);
    $year = FiscalYear::create(['company_id' => $this->company->id, 'name' => 'FY', 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(), 'status' => 'OPEN']);
    FiscalPeriod::create(['fiscal_year_id' => $year->id, 'period_name' => 'Y', 'period_number' => 1, 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(), 'status' => 'OPEN']);

    $this->revenue = Account::factory()->revenue()->create(['company_id' => $this->company->id]);
    $this->receivable = Account::factory()->asset()->create(['company_id' => $this->company->id]);
    $this->outputVat = Account::factory()->liability()->create(['company_id' => $this->company->id]);
    $this->vat = Tax::factory()->create(['company_id' => $this->company->id, 'tax_code' => 'VAT15', 'rate' => 15, 'is_inclusive' => false, 'is_group' => false, 'output_account_id' => $this->outputVat->id]);
    $this->customer = Customer::factory()->create(['company_id' => $this->company->id, 'receivable_account_id' => $this->receivable->id]);
    $this->clerk = companyUser(['finance.customer-credit-notes.view', 'finance.customer-credit-notes.create', 'finance.customer-invoices.view'], $this->company);
    $this->approver = companyUser([], $this->company);

    $invoices = app(CustomerInvoiceService::class);
    $this->actingAs($this->clerk);
    $invoice = $invoices->submitInvoice($invoices->createInvoice([
        'company_id' => $this->company->id, 'customer_id' => $this->customer->id,
        'invoice_date' => now()->toDateString(), 'due_date' => now()->addMonth()->toDateString(),
        'lines' => [['account_id' => $this->revenue->id, 'description' => 'Licences', 'quantity' => 2, 'unit_price' => 100, 'tax_id' => $this->vat->id]],
    ]));
    $this->actingAs($this->approver);
    $this->invoice = $invoices->postInvoice($invoices->approveInvoice($invoice));
});

function creditNoteFor(CustomerInvoice $invoice, string $unitPrice, int $quantity = 1): CustomerCreditNote
{
    test()->actingAs(test()->clerk);

    return app(CustomerCreditNoteService::class)->create([
        'company_id' => $invoice->company_id, 'customer_id' => $invoice->customer_id, 'customer_invoice_id' => $invoice->id,
        'note_date' => now()->toDateString(), 'description' => 'Licence returned',
        'lines' => [['account_id' => test()->revenue->id, 'description' => 'Licence returned', 'quantity' => $quantity, 'unit_price' => $unitPrice, 'tax_id' => test()->vat->id]],
    ]);
}

function postedCredit(CustomerCreditNote $creditNote): CustomerCreditNote
{
    $service = app(CustomerCreditNoteService::class);
    test()->actingAs(test()->clerk);
    $service->submit($creditNote);
    test()->actingAs(test()->approver);

    return $service->post($service->approve($creditNote->fresh()));
}

function creditNoteLedgerBalance(int $accountId): string
{
    return (string) JournalLine::where('account_id', $accountId)->get()
        ->reduce(fn ($total, $line) => bcadd($total, bcsub($line->debit, $line->credit, 4), 4), '0');
}

test('posting a credit note reverses revenue and output tax and reduces what the customer owes', function () {
    $creditNote = postedCredit(creditNoteFor($this->invoice, '100'));

    expect(creditNoteLedgerBalance($this->revenue->id))->toBe('-100.0000')
        ->and(creditNoteLedgerBalance($this->outputVat->id))->toBe('-15.0000')
        ->and(creditNoteLedgerBalance($this->receivable->id))->toBe('115.0000')
        ->and((string) $creditNote->applied_amount)->toBe('115.0000')
        ->and((string) $this->invoice->fresh()->outstanding_amount)->toBe('115.0000')
        ->and((string) TaxTransaction::where('customer_credit_note_id', $creditNote->id)->value('tax_amount'))->toBe('-15.0000');

    $invoice = $this->invoice->fresh();
    $invoice->calculateOutstanding();
    expect((string) $invoice->outstanding_amount)->toBe('115.0000');
});

test('a credit larger than what is owed settles the invoice and leaves the rest as unapplied credit', function () {
    $creditNote = postedCredit(creditNoteFor($this->invoice, '100', 3));

    expect($this->invoice->fresh()->status)->toBe(CustomerInvoice::STATUS_PAID)
        ->and((string) $creditNote->applied_amount)->toBe('230.0000')
        ->and($creditNote->unappliedAmount())->toBe('115.0000');
});

test('the creator of a credit note cannot approve it', function () {
    $service = app(CustomerCreditNoteService::class);
    $creditNote = $service->submit(creditNoteFor($this->invoice, '50'));

    expect(fn () => $service->approve($creditNote))->toThrow(InvalidAccountingTransactionException::class);
});

test('a credit note is drafted on screen from the invoice it credits', function () {
    actingInCompany($this->clerk, $this->company)
        ->get(route('finance.customer-credit-notes.create', ['invoice' => $this->invoice->id]))
        ->assertOk()
        ->assertSee('Licences')
        ->assertSee('Credit for invoice '.$this->invoice->invoice_number);

    actingInCompany($this->clerk, $this->company)
        ->post(route('finance.customer-credit-notes.store'), [
            'customer_id' => $this->customer->id, 'customer_invoice_id' => $this->invoice->id,
            'note_date' => now()->toDateString(), 'description' => 'Price correction',
            'lines' => [['account_id' => $this->revenue->id, 'description' => 'Discount agreed', 'quantity' => 1, 'unit_price' => 20, 'tax_id' => $this->vat->id]],
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $creditNote = CustomerCreditNote::sole();

    expect((string) $creditNote->total_amount)->toBe('23.0000')
        ->and($creditNote->note_number)->toStartWith('CN-');

    actingInCompany($this->clerk, $this->company)
        ->get(route('finance.customer-credit-notes.show', $creditNote->id))
        ->assertOk()
        ->assertSee('Discount agreed');
});

test('a credit note cannot credit another customer\'s invoice', function () {
    $otherCustomer = Customer::factory()->create(['company_id' => $this->company->id]);

    actingInCompany($this->clerk, $this->company)
        ->post(route('finance.customer-credit-notes.store'), [
            'customer_id' => $otherCustomer->id, 'customer_invoice_id' => $this->invoice->id,
            'note_date' => now()->toDateString(), 'description' => 'Wrong customer',
            'lines' => [['account_id' => $this->revenue->id, 'description' => 'X', 'quantity' => 1, 'unit_price' => 10]],
        ])
        ->assertSessionHasErrors('customer_invoice_id');
});

test('the customer statement lists posted invoices and credit notes in date order with a running balance', function () {
    postedCredit(creditNoteFor($this->invoice, '100'));
    app(CustomerInvoiceService::class)->createInvoice([
        'company_id' => $this->company->id, 'customer_id' => $this->customer->id, 'invoice_date' => now()->toDateString(), 'due_date' => now()->toDateString(),
        'lines' => [['account_id' => $this->revenue->id, 'description' => 'Draft only', 'quantity' => 1, 'unit_price' => 999]],
    ]);

    actingInCompany(companyUser(['finance.customers.view', 'finance.reports.view', 'finance.customer-invoices.view'], $this->company), $this->company)
        ->get(route('finance.customer-statements.show', $this->customer->id))
        ->assertOk()
        ->assertViewHas('statement', fn (array $statement) => $statement['closing_balance'] === '115.0000'
            && count($statement['entries']) === 2
            && $statement['entries'][1]['type'] === 'credit_note')
        ->assertSee('Credit note');
});
