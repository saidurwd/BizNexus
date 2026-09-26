<?php

use Modules\Core\Models\Company;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\Customer;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Models\Tax;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->customer = Customer::factory()->create(['company_id' => $this->company->id]);
    $this->revenue = Account::factory()->revenue()->create(['company_id' => $this->company->id]);
    $this->vat = Tax::factory()->create(['company_id' => $this->company->id, 'tax_code' => 'VAT15', 'rate' => 15, 'is_inclusive' => false, 'is_group' => false]);
    $this->user = companyUser(['finance.customer-invoices.view', 'finance.customer-invoices.create', 'finance.customer-invoices.update'], $this->company);
});

function invoiceForm(array $overrides = []): array
{
    return [
        'customer_id' => test()->customer->id,
        'invoice_date' => '2026-06-01',
        'due_date' => '2026-07-01',
        'lines' => [
            ['account_id' => test()->revenue->id, 'description' => 'Consulting', 'quantity' => 2, 'unit_price' => 50, 'tax_id' => test()->vat->id],
            ['account_id' => test()->revenue->id, 'description' => 'Travel', 'quantity' => 1, 'unit_price' => 50],
        ],
        ...$overrides,
    ];
}

test('an invoice is created with its lines, tax and an automatic number', function () {
    actingInCompany($this->user, $this->company)
        ->post(route('finance.customer-invoices.store'), invoiceForm())
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $invoice = CustomerInvoice::withoutGlobalScopes()->with('lines')->sole();

    expect($invoice->lines)->toHaveCount(2)
        ->and($invoice->invoice_number)->not->toBeEmpty()
        ->and((string) $invoice->subtotal)->toBe('150.0000')
        ->and((string) $invoice->tax_amount)->toBe('15.0000')
        ->and((string) $invoice->total_amount)->toBe('165.0000');

    actingInCompany($this->user, $this->company)
        ->get(route('finance.customer-invoices.show', $invoice->id))
        ->assertOk()
        ->assertSeeInOrder(['Consulting', 'Travel']);
});

test('an invoice needs lines on accounts of the active company', function () {
    $foreignAccount = Account::factory()->revenue()->create();

    actingInCompany($this->user, $this->company)
        ->post(route('finance.customer-invoices.store'), invoiceForm(['lines' => [['account_id' => $foreignAccount->id, 'description' => 'X', 'quantity' => 1, 'unit_price' => 10]]]))
        ->assertSessionHasErrors('lines.0.account_id');

    actingInCompany($this->user, $this->company)
        ->post(route('finance.customer-invoices.store'), invoiceForm(['lines' => []]))
        ->assertSessionHasErrors('lines');

    expect(CustomerInvoice::withoutGlobalScopes()->count())->toBe(0);
});

test('editing a draft replaces its lines', function () {
    actingInCompany($this->user, $this->company)->post(route('finance.customer-invoices.store'), invoiceForm());
    $invoice = CustomerInvoice::withoutGlobalScopes()->sole();

    actingInCompany($this->user, $this->company)->get(route('finance.customer-invoices.edit', $invoice->id))->assertOk()->assertSee('Consulting');

    actingInCompany($this->user, $this->company)
        ->put(route('finance.customer-invoices.update', $invoice->id), invoiceForm(['lines' => [['account_id' => $this->revenue->id, 'description' => 'Workshop', 'quantity' => 1, 'unit_price' => 300]]]))
        ->assertRedirect(route('finance.customer-invoices.show', $invoice->id));

    expect($invoice->fresh()->lines->pluck('description')->all())->toBe(['Workshop'])
        ->and((string) $invoice->fresh()->total_amount)->toBe('300.0000');
});

test('the new invoice form renders the line editor', function () {
    actingInCompany($this->user, $this->company)
        ->get(route('finance.customer-invoices.create'))
        ->assertOk()
        ->assertSee('data-add-line', false)
        ->assertSee($this->revenue->account_name);
});
