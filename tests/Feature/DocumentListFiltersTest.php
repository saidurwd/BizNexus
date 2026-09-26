<?php

use Illuminate\Support\Carbon;
use Modules\Core\Models\Company;
use Modules\Finance\Models\Customer;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Models\Journal;

beforeEach(function () {
    Carbon::setTestNow('2026-06-30 12:00:00');
    $this->company = Company::factory()->create();
    $this->acme = Customer::factory()->create(['company_id' => $this->company->id, 'name' => 'Acme GmbH']);
    $this->globex = Customer::factory()->create(['company_id' => $this->company->id, 'name' => 'Globex Ltd']);
    $this->user = companyUser(['finance.customer-invoices.view', 'finance.journals.view'], $this->company);
});

function listedInvoice(Customer $customer, string $number, string $status, string $date, string $dueDate): void
{
    CustomerInvoice::create([
        'company_id' => $customer->company_id, 'customer_id' => $customer->id, 'invoice_number' => $number,
        'invoice_date' => $date, 'due_date' => $dueDate, 'total_amount' => 100, 'outstanding_amount' => 100, 'status' => $status,
    ]);
}

test('invoices are filtered by search, status, date range and overdue', function () {
    listedInvoice($this->acme, 'INV-1001', CustomerInvoice::STATUS_POSTED, '2026-05-01', '2026-05-31');
    listedInvoice($this->globex, 'INV-1002', CustomerInvoice::STATUS_DRAFT, '2026-06-10', '2026-07-10');
    listedInvoice($this->globex, 'INV-1003', CustomerInvoice::STATUS_POSTED, '2026-06-20', '2026-07-20');
    $list = fn (array $query) => actingInCompany($this->user, $this->company)->get(route('finance.customer-invoices.index', $query))->assertOk();

    $list(['q' => 'globex'])->assertSee('INV-1002')->assertSee('INV-1003')->assertDontSee('INV-1001');
    $list(['q' => '1001'])->assertSee('INV-1001')->assertDontSee('INV-1002');
    $list(['status' => 'DRAFT'])->assertSee('INV-1002')->assertDontSee('INV-1003');
    $list(['from' => '2026-06-01', 'to' => '2026-06-15'])->assertSee('INV-1002')->assertDontSee('INV-1001')->assertDontSee('INV-1003');
    $list(['overdue' => 1])->assertSee('INV-1001')->assertDontSee('INV-1003');
});

test('lists page beyond the first twenty documents and keep the filters on the page links', function () {
    Journal::factory()->count(25)->create(['company_id' => $this->company->id, 'status' => Journal::STATUS_DRAFT]);

    actingInCompany($this->user, $this->company)
        ->get(route('finance.journals.index', ['status' => 'DRAFT']))
        ->assertOk()
        ->assertSee('status=DRAFT&amp;page=2', false);
});
