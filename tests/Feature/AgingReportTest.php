<?php

use Illuminate\Support\Carbon;
use Modules\Core\Models\Company;
use Modules\Core\Models\Currency;
use Modules\Finance\Models\Customer;
use Modules\Finance\Models\CustomerInvoice;

beforeEach(function () {
    Carbon::setTestNow('2026-06-30 12:00:00');
    $this->company = Company::factory()->create();
    $this->customer = Customer::factory()->create(['company_id' => $this->company->id, 'name' => 'Acme GmbH']);
    $this->euro = Currency::factory()->create(['code' => 'EUR', 'decimal_places' => 2]);
});

function openInvoice(Customer $customer, string $number, string $dueDate, string $outstanding, string $rate = '1', ?int $currencyId = null): void
{
    CustomerInvoice::create([
        'company_id' => $customer->company_id, 'customer_id' => $customer->id, 'invoice_number' => $number,
        'invoice_date' => '2026-01-01', 'due_date' => $dueDate, 'currency_id' => $currencyId, 'exchange_rate' => $rate,
        'total_amount' => $outstanding, 'outstanding_amount' => $outstanding, 'status' => CustomerInvoice::STATUS_POSTED,
    ]);
}

test('invoices are aged by days past due and converted to the functional currency', function () {
    openInvoice($this->customer, 'INV-1', '2026-07-20', '100');
    openInvoice($this->customer, 'INV-2', '2026-05-16', '200', '1.1', $this->euro->id);

    actingInCompany(companyUser(['finance.reports.view'], $this->company), $this->company)
        ->get(route('finance.ar-aging'))
        ->assertOk()
        ->assertViewHas('aging', fn (array $aging) => $aging['current'] === '100.0000'
            && $aging['days_31_60'] === '220.0000'
            && $aging['days_1_30'] === '0.0000'
            && $aging['parties'][0]['party_name'] === 'Acme GmbH'
            && $aging['parties'][0]['total'] === '320.0000')
        ->assertSee('320.00');
});

test('an invoice due in the future is not overdue', function () {
    openInvoice($this->customer, 'INV-3', '2026-07-10', '50');

    expect(CustomerInvoice::withoutGlobalScopes()->where('invoice_number', 'INV-3')->first()->getDaysOutstanding())->toBe(0);
});
