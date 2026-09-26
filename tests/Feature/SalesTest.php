<?php

use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Models\Company;
use Modules\Core\Models\FiscalPeriod;
use Modules\Core\Models\FiscalYear;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Support\Money;
use Modules\Finance\Enums\AccountPurpose;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\AccountMapping;
use Modules\Finance\Models\Customer;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Models\JournalLine;
use Modules\Finance\Services\CustomerCreditNoteService;
use Modules\Finance\Services\CustomerInvoiceService;
use Modules\Inventory\Contracts\StockReservations;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\StockService;
use Modules\Sales\Models\DeliveryNote;
use Modules\Sales\Models\Quotation;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Services\DeliveryService;
use Modules\Sales\Services\SalesOrderCostService;

beforeEach(function () {
    $this->company = Company::factory()->create();
    app(CompanyContextService::class)->pinCompany($this->company->id);
    $year = FiscalYear::create(['company_id' => $this->company->id, 'name' => 'FY', 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(), 'status' => 'OPEN']);
    FiscalPeriod::create(['fiscal_year_id' => $year->id, 'period_name' => 'Y', 'period_number' => 1, 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(), 'status' => 'OPEN']);

    $this->accounts = [
        'inventory' => Account::factory()->asset()->create(['company_id' => $this->company->id]),
        'delivered' => Account::factory()->asset()->create(['company_id' => $this->company->id]),
        'cogs' => Account::factory()->expense()->create(['company_id' => $this->company->id]),
        'revenue' => Account::factory()->revenue()->create(['company_id' => $this->company->id]),
        'receivable' => Account::factory()->asset()->create(['company_id' => $this->company->id]),
    ];
    foreach ([AccountPurpose::Inventory->value => 'inventory', AccountPurpose::GoodsDeliveredNotInvoiced->value => 'delivered', AccountPurpose::CostOfGoodsSold->value => 'cogs'] as $purpose => $key) {
        AccountMapping::create(['company_id' => $this->company->id, 'purpose' => $purpose, 'account_id' => $this->accounts[$key]->id]);
    }

    $this->customer = Customer::factory()->create(['company_id' => $this->company->id, 'receivable_account_id' => $this->accounts['receivable']->id]);
    $this->warehouse = Warehouse::where('company_id', $this->company->id)->sole();
    $this->chair = Product::factory()->create(['company_id' => $this->company->id, 'sku' => 'CHAIR', 'sales_price' => 100, 'revenue_account_id' => $this->accounts['revenue']->id]);
    $this->setup = Product::factory()->service()->create(['company_id' => $this->company->id, 'sku' => 'SETUP', 'sales_price' => 50, 'revenue_account_id' => $this->accounts['revenue']->id]);

    $this->seller = companyUser(['sales.quotations.view', 'sales.quotations.manage', 'sales.orders.view', 'sales.orders.create', 'sales.orders.confirm', 'sales.orders.cancel', 'sales.deliveries.view', 'sales.deliveries.create', 'finance.customer-invoices.create', 'finance.customer-invoices.view', 'finance.customer-invoices.submit'], $this->company);
    $this->approver = companyUser(['finance.customer-invoices.approve', 'finance.customer-invoices.post'], $this->company);

    $this->actingAs($this->seller);
    app(StockService::class)->receive($this->chair, $this->warehouse->id, '10', Money::of('400', 'USD'), now()->toDateString(), ['type' => 'test', 'id' => 1]);
});

function salesLedger(Account $account): string
{
    return (string) JournalLine::where('account_id', $account->id)->whereHas('journal', fn ($query) => $query->posted())
        ->get()->reduce(fn ($total, $line) => bcadd($total, bcsub($line->debit, $line->credit, 4), 4), '0');
}

function confirmedOrder(array $lines): SalesOrder
{
    actingInCompany(test()->seller, test()->company)->post(route('sales.orders.store'), [
        'customer_id' => test()->customer->id, 'order_date' => now()->toDateString(), 'warehouse_id' => test()->warehouse->id,
        'lines' => array_map(fn (array $line) => ['product_id' => $line[0]->id, 'quantity' => $line[1], 'unit_price' => $line[2]], $lines),
    ])->assertSessionHasNoErrors();
    $order = SalesOrder::latest('id')->first();
    actingInCompany(test()->seller, test()->company)->post(route('sales.orders.confirm', $order->id))->assertSessionHas('success');

    return $order->fresh('lines');
}

function postSalesInvoice(CustomerInvoice $invoice): CustomerInvoice
{
    $service = app(CustomerInvoiceService::class);
    test()->actingAs(test()->seller);
    $service->submitInvoice($invoice);
    test()->actingAs(test()->approver);

    return $service->postInvoice($service->approveInvoice($invoice->fresh()));
}

test('a quotation is sent, accepted and turned into a sales order with the same lines', function () {
    actingInCompany($this->seller, $this->company)->post(route('sales.quotations.store'), [
        'customer_id' => $this->customer->id, 'quotation_date' => now()->toDateString(), 'valid_until' => now()->addMonth()->toDateString(),
        'lines' => [['product_id' => $this->chair->id, 'quantity' => 2, 'unit_price' => 100, 'discount_amount' => 20], ['product_id' => $this->setup->id, 'quantity' => 1, 'unit_price' => 50]],
    ])->assertSessionHasNoErrors();
    $quotation = Quotation::sole();

    expect($quotation->quotation_number)->toStartWith('QT')->and($quotation->total_amount)->toBe('230.0000');

    actingInCompany($this->seller, $this->company)->post(route('sales.quotations.send', $quotation->id))->assertSessionHas('success');
    actingInCompany($this->seller, $this->company)->post(route('sales.quotations.accept', $quotation->id))->assertSessionHas('success');
    actingInCompany($this->seller, $this->company)->post(route('sales.quotations.convert', $quotation->id), ['order_date' => now()->toDateString(), 'warehouse_id' => $this->warehouse->id])
        ->assertRedirect();

    $order = SalesOrder::with('lines')->sole();
    expect($quotation->fresh()->status)->toBe('CONVERTED')
        ->and($order)->sales_quotation_id->toBe($quotation->id)->status->toBe('DRAFT')->total_amount->toBe('230.0000')
        ->and($order->lines->pluck('discount_amount')->all())->toBe(['20.0000', '0.0000']);

    actingInCompany($this->seller, $this->company)->get(route('sales.quotations.show', $quotation->id))->assertOk()->assertSee($order->order_number);
    actingInCompany($this->seller, $this->company)->get(route('sales.quotations.pdf', $quotation->id))->assertOk()->assertHeader('content-type', 'application/pdf');
});

test('confirming an order is refused when it would take the customer over their credit limit', function () {
    config(['finance.controls.credit_limit' => 'block']);
    $this->customer->update(['credit_limit' => 500]);

    actingInCompany($this->seller, $this->company)->post(route('sales.orders.store'), [
        'customer_id' => $this->customer->id, 'order_date' => now()->toDateString(), 'warehouse_id' => $this->warehouse->id,
        'lines' => [['product_id' => $this->chair->id, 'quantity' => 6, 'unit_price' => 100]],
    ]);
    $order = SalesOrder::sole();

    actingInCompany($this->seller, $this->company)->post(route('sales.orders.confirm', $order->id))->assertSessionHas('error');
    expect($order->fresh()->status)->toBe('DRAFT');
});

test('a delivery takes stock out at average cost into goods delivered not invoiced', function () {
    $order = confirmedOrder([[$this->chair, 6, 100]]);

    actingInCompany($this->seller, $this->company)->post(route('sales.deliveries.store', $order->id), [
        'delivery_date' => now()->toDateString(), 'warehouse_id' => $this->warehouse->id, 'carrier' => 'DHL', 'lines' => [$order->lines[0]->id => 4],
    ])->assertRedirect();

    expect($this->chair->fresh()->stock_quantity)->toBe('6.0000')
        ->and(salesLedger($this->accounts['delivered']))->toBe('160.0000')
        ->and(salesLedger($this->accounts['inventory']))->toBe('-160.0000')
        ->and($order->fresh()->status)->toBe('PARTIALLY_DELIVERED')
        ->and($order->lines[0]->fresh())->delivered_quantity->toBe('4.0000')->delivered_cost_value->toBe('160.0000');

    $this->actingAs($this->seller);
    expect(fn () => app(DeliveryService::class)->deliver($order, ['delivery_date' => now()->toDateString(), 'lines' => [$order->lines[0]->id => 3]]))
        ->toThrow(InvalidAccountingTransactionException::class, 'Only 2 of CHAIR is still to be delivered on this order.');

    $delivery = DeliveryNote::sole();
    actingInCompany($this->seller, $this->company)->get(route('sales.deliveries.show', $delivery->id))->assertOk()->assertSee('DHL');
    actingInCompany($this->seller, $this->company)->get(route('sales.deliveries.pdf', $delivery->id))->assertOk()->assertHeader('content-type', 'application/pdf');
});

test('a delivery cannot take more than the warehouse holds', function () {
    $order = confirmedOrder([[$this->chair, 12, 100]]);
    $this->actingAs($this->seller);

    expect(fn () => app(DeliveryService::class)->deliver($order, ['delivery_date' => now()->toDateString(), 'lines' => [$order->lines[0]->id => 12]]))
        ->toThrow(InvalidAccountingTransactionException::class, 'Not enough CHAIR');
    expect(DeliveryNote::count())->toBe(0)->and($this->chair->fresh()->stock_quantity)->toBe('10.0000');
});

test('the order invoice bills what was delivered and moves its cost to cost of goods sold', function () {
    $order = confirmedOrder([[$this->chair, 5, 100], [$this->setup, 1, 50]]);
    $this->actingAs($this->seller);
    app(DeliveryService::class)->deliver($order, ['delivery_date' => now()->toDateString(), 'lines' => [$order->lines[0]->id => 5]]);
    $invoicing = app(SalesOrderCostService::class);

    expect(fn () => $invoicing->createInvoice($order->fresh(), ['invoice_date' => now()->toDateString(), 'lines' => [$order->lines[0]->id => 6]]))
        ->toThrow(InvalidAccountingTransactionException::class, 'CHAIR: the invoice bills 6 but only 5 can be invoiced');

    actingInCompany($this->seller, $this->company)->get(route('sales.orders.invoice.create', $order->id))->assertOk()->assertSee('SETUP');
    actingInCompany($this->seller, $this->company)->post(route('sales.orders.invoice.store', $order->id), [
        'invoice_date' => now()->toDateString(), 'lines' => [$order->lines[0]->id => 5, $order->lines[1]->id => 1],
    ])->assertRedirect();
    $invoice = CustomerInvoice::sole();

    expect($invoice->sales_order_id)->toBe($order->id)->and($invoice->total_amount)->toBe('550.0000');
    actingInCompany(companyUser(['finance.customer-invoices.update'], $this->company), $this->company)->get(route('finance.customer-invoices.edit', $invoice->id))->assertRedirect();

    $posted = postSalesInvoice($invoice);

    expect(salesLedger($this->accounts['revenue']))->toBe('-550.0000')
        ->and(salesLedger($this->accounts['cogs']))->toBe('200.0000')
        ->and(salesLedger($this->accounts['delivered']))->toBe('0.0000')
        ->and($this->chair->fresh()->stock_quantity)->toBe('5.0000')
        ->and($posted->cost_journal_id)->not->toBeNull()
        ->and($order->fresh()->status)->toBe('CLOSED');
});

test('a service on an order can be invoiced before anything is delivered and the order cannot be cancelled after', function () {
    $order = confirmedOrder([[$this->chair, 2, 100], [$this->setup, 1, 50]]);
    $this->actingAs($this->seller);

    $invoice = app(SalesOrderCostService::class)->createInvoice($order, ['invoice_date' => now()->toDateString(), 'lines' => [$order->lines[1]->id => 1]]);
    postSalesInvoice($invoice);

    expect($order->lines[1]->fresh()->invoiced_quantity)->toBe('1.0000')
        ->and(salesLedger($this->accounts['cogs']))->toBe('0')
        ->and($order->fresh()->status)->toBe('CONFIRMED');

    actingInCompany($this->seller, $this->company)->post(route('sales.orders.cancel', $order->id))->assertSessionHas('error');
});

test('a credit note returning goods to a warehouse puts them back at their original cost', function () {
    $service = app(CustomerInvoiceService::class);
    $this->actingAs($this->seller);
    $invoice = postSalesInvoice($service->createInvoice([
        'company_id' => $this->company->id, 'customer_id' => $this->customer->id, 'invoice_date' => now()->toDateString(),
        'lines' => [['product_id' => $this->chair->id, 'warehouse_id' => $this->warehouse->id, 'account_id' => $this->accounts['revenue']->id, 'description' => 'Chairs', 'quantity' => 3, 'unit_price' => 100]],
    ]));
    app(StockService::class)->receive($this->chair, $this->warehouse->id, '7', Money::of('700', 'USD'), now()->toDateString(), ['type' => 'test', 'id' => 2]);

    $credits = app(CustomerCreditNoteService::class);
    $this->actingAs($this->seller);
    $creditNote = $credits->create([
        'company_id' => $this->company->id, 'customer_id' => $this->customer->id, 'customer_invoice_id' => $invoice->id, 'note_date' => now()->toDateString(), 'description' => 'Returned',
        'lines' => [['product_id' => $this->chair->id, 'warehouse_id' => $this->warehouse->id, 'account_id' => $this->accounts['revenue']->id, 'description' => 'Chair returned', 'quantity' => 1, 'unit_price' => 100]],
    ]);
    $credits->submit($creditNote);
    $this->actingAs($this->approver);
    $posted = $credits->post($credits->approve($creditNote->fresh()));

    expect($posted->cost_journal_id)->not->toBeNull()
        ->and($posted->lines->first()->cost_value)->toBe('40.0000')
        ->and(salesLedger($this->accounts['cogs']))->toBe('80.0000')
        ->and($this->chair->fresh()->stock_quantity)->toBe('15.0000');
});

test('the sales screens list quotations, orders and deliveries', function () {
    $order = confirmedOrder([[$this->chair, 1, 100]]);

    actingInCompany($this->seller, $this->company)->get(route('sales.orders.index'))->assertOk()->assertSee($order->order_number);
    actingInCompany($this->seller, $this->company)->get(route('sales.orders.show', $order->id))->assertOk()->assertSee('CHAIR');
    actingInCompany($this->seller, $this->company)->get(route('sales.orders.pdf', $order->id))->assertOk();
    actingInCompany($this->seller, $this->company)->get(route('sales.deliveries.create', $order->id))->assertOk()->assertSee('CHAIR');
    actingInCompany($this->seller, $this->company)->get(route('sales.quotations.index'))->assertOk();
    actingInCompany($this->seller, $this->company)->get(route('sales.deliveries.index'))->assertOk();
});

test('confirmed orders reserve their undelivered stock until it ships', function () {
    $order = confirmedOrder([[$this->chair, 6, 100]]);
    $reservations = app(StockReservations::class);

    expect($reservations->reserved([$this->chair->id]))->toBe([$this->chair->id => '6.0000']);

    $this->actingAs($this->seller);
    app(DeliveryService::class)->deliver($order, ['delivery_date' => now()->toDateString(), 'lines' => [$order->lines[0]->id => 4]]);

    expect($reservations->reserved([$this->chair->id], $this->warehouse->id))->toBe([$this->chair->id => '2.0000'])
        ->and($reservations->reserved([$this->chair->id], null, $order->id))->toBe([]);

    $viewer = companyUser(['inventory.stock.view', 'inventory.products.view'], $this->company);
    actingInCompany($viewer, $this->company)->get(route('inventory.stock.index'))->assertOk()->assertSeeInOrder(['CHAIR', '6', '2', '4']);
    actingInCompany($viewer, $this->company)->get(route('inventory.products.show', $this->chair->id))->assertOk()->assertSee('Reserved for sales orders');
});

test('confirming an order that needs more than is free warns of a backorder, or refuses it when set to block', function () {
    confirmedOrder([[$this->chair, 8, 100]]);

    actingInCompany($this->seller, $this->company)->post(route('sales.orders.store'), [
        'customer_id' => $this->customer->id, 'order_date' => now()->toDateString(), 'warehouse_id' => $this->warehouse->id,
        'lines' => [['product_id' => $this->chair->id, 'quantity' => 3, 'unit_price' => 100]],
    ]);
    $second = SalesOrder::latest('id')->first();

    config(['inventory.reservation_check' => 'block']);
    actingInCompany($this->seller, $this->company)->post(route('sales.orders.confirm', $second->id))
        ->assertSessionHas('error', 'Not enough stock is free in MAIN, so part of the order will be on backorder (CHAIR: 2 available, 3 ordered).');
    expect($second->fresh()->status)->toBe('DRAFT');

    config(['inventory.reservation_check' => 'warn']);
    actingInCompany($this->seller, $this->company)->post(route('sales.orders.confirm', $second->id))->assertSessionHas('warning');
    expect($second->fresh()->status)->toBe('CONFIRMED');
});
