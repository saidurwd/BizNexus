<?php

use Carbon\CarbonImmutable;
use Modules\Core\Models\Company;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\Customer;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Models\PaymentTerm;
use Modules\Finance\Models\Supplier;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->terms = PaymentTerm::withoutGlobalScopes()->where('company_id', $this->company->id)->get()->keyBy('code');
    $this->revenue = Account::factory()->revenue()->create(['company_id' => $this->company->id]);
});

test('a new company gets the usual payment terms, which work out due dates', function () {
    $invoiceDate = CarbonImmutable::parse('2026-01-20');

    expect($this->terms->keys()->sort()->values()->all())->toBe(['DUE', 'EOM30', 'NET15', 'NET30', 'NET60'])
        ->and($this->terms['NET30']->dueDate($invoiceDate)->toDateString())->toBe('2026-02-19')
        ->and($this->terms['EOM30']->dueDate($invoiceDate)->toDateString())->toBe('2026-03-02')
        ->and($this->terms['DUE']->dueDate($invoiceDate)->toDateString())->toBe('2026-01-20');
});

test('customers are created and edited with a payment term and credit limit, and shown with their credit position', function () {
    $user = companyUser(['finance.customers.view', 'finance.customers.create', 'finance.customers.update'], $this->company);

    actingInCompany($user, $this->company)->post(route('finance.customers.store'), [
        'customer_code' => 'C-100', 'name' => 'Globex Ltd', 'email' => 'ap@globex.test', 'country_code' => 'GB',
        'payment_term_id' => $this->terms['NET30']->id, 'credit_limit' => 5000, 'status' => 'active',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $customer = Customer::withoutGlobalScopes()->sole();
    expect($customer->payment_term_id)->toBe($this->terms['NET30']->id);

    actingInCompany($user, $this->company)->put(route('finance.customers.update', $customer->id), [
        'customer_code' => 'C-100', 'name' => 'Globex Limited', 'payment_term_id' => $this->terms['NET60']->id, 'status' => 'active',
    ])->assertRedirect(route('finance.customers.show', $customer->id));

    actingInCompany($user, $this->company)->get(route('finance.customers.show', $customer->id))
        ->assertOk()
        ->assertSee('Globex Limited')
        ->assertSee('Net 60 days')
        ->assertSee('Customer updated.');
    actingInCompany($user, $this->company)->get(route('finance.customers.index', ['q' => 'globex']))->assertOk()->assertSee('C-100');
});

test('party codes are unique per company, not across companies', function () {
    $otherCompany = Company::factory()->create();
    Supplier::factory()->create(['company_id' => $otherCompany->id, 'supplier_code' => 'S-1']);
    $user = companyUser(['finance.suppliers.create'], $this->company);

    actingInCompany($user, $this->company)->post(route('finance.suppliers.store'), ['supplier_code' => 'S-1', 'name' => 'Initech', 'status' => 'active'])
        ->assertSessionHasNoErrors();
    actingInCompany($user, $this->company)->post(route('finance.suppliers.store'), ['supplier_code' => 'S-1', 'name' => 'Initech again', 'status' => 'active'])
        ->assertSessionHasErrors('supplier_code');
});

test('an invoice without a due date gets it from the customer payment term', function () {
    $customer = Customer::factory()->create(['company_id' => $this->company->id, 'payment_term_id' => $this->terms['EOM30']->id]);

    actingInCompany(companyUser(['finance.customer-invoices.create', 'finance.customer-invoices.view'], $this->company), $this->company)
        ->post(route('finance.customer-invoices.store'), [
            'customer_id' => $customer->id, 'invoice_date' => '2026-01-20',
            'lines' => [['account_id' => $this->revenue->id, 'description' => 'Service', 'quantity' => 1, 'unit_price' => 100]],
        ])->assertSessionHasNoErrors();

    expect(CustomerInvoice::withoutGlobalScopes()->sole()->due_date->toDateString())->toBe('2026-03-02');
});

test('submitting an invoice above the customer credit limit is refused with a message', function () {
    $customer = Customer::factory()->create(['company_id' => $this->company->id, 'credit_limit' => 50, 'name' => 'Small Buyer']);
    $user = companyUser(['finance.customer-invoices.create', 'finance.customer-invoices.submit', 'finance.customer-invoices.view'], $this->company);
    actingInCompany($user, $this->company)->post(route('finance.customer-invoices.store'), [
        'customer_id' => $customer->id, 'invoice_date' => '2026-01-20', 'due_date' => '2026-02-20',
        'lines' => [['account_id' => $this->revenue->id, 'description' => 'Service', 'quantity' => 1, 'unit_price' => 100]],
    ]);
    $invoice = CustomerInvoice::withoutGlobalScopes()->sole();

    actingInCompany($user, $this->company)
        ->from(route('finance.customer-invoices.show', $invoice->id))
        ->post(route('finance.customer-invoices.submit', $invoice->id))
        ->assertRedirect(route('finance.customer-invoices.show', $invoice->id))
        ->assertSessionHas('error', fn (string $message) => str_contains($message, 'above the credit limit'));

    expect($invoice->fresh()->status)->toBe(CustomerInvoice::STATUS_DRAFT);

    config(['finance.controls.credit_limit' => 'warn']);
    app(CompanyContextService::class)->pinCompany($this->company->id);
    actingInCompany($user, $this->company)->post(route('finance.customer-invoices.submit', $invoice->id))->assertSessionHas('warning');

    expect($invoice->fresh()->status)->toBe(CustomerInvoice::STATUS_SUBMITTED);
});

test('payment terms are managed on their own screen and cannot be deleted while in use', function () {
    $admin = companyUser(['finance.payment-terms.view', 'finance.payment-terms.manage'], $this->company);
    Customer::factory()->create(['company_id' => $this->company->id, 'payment_term_id' => $this->terms['NET30']->id]);

    actingInCompany($admin, $this->company)->post(route('finance.payment-terms.store'), [
        'code' => '2-10N30', 'name' => '2% 10, net 30', 'due_days' => 30, 'due_basis' => 'invoice_date', 'discount_percent' => 2, 'discount_days' => 10, 'status' => 'active',
    ])->assertSessionHasNoErrors();

    actingInCompany($admin, $this->company)->get(route('finance.payment-terms.index'))->assertOk()->assertSee('2% discount if paid within 10 days');
    actingInCompany($admin, $this->company)->delete(route('finance.payment-terms.destroy', $this->terms['NET30']->id))->assertSessionHas('error');
    expect(PaymentTerm::withoutGlobalScopes()->whereKey($this->terms['NET30']->id)->exists())->toBeTrue();
});
