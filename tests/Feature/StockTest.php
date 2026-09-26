<?php

use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Models\Company;
use Modules\Core\Models\FiscalPeriod;
use Modules\Core\Models\FiscalYear;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Enums\AccountPurpose;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\AccountMapping;
use Modules\Finance\Models\Customer;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Models\Journal;
use Modules\Finance\Models\JournalLine;
use Modules\Finance\Services\CustomerInvoiceService;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\StockAdjustment;
use Modules\Inventory\Models\StockBalance;
use Modules\Inventory\Models\StockMove;
use Modules\Inventory\Models\StockTransfer;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\StockService;

beforeEach(function () {
    $this->company = Company::factory()->create();
    app(CompanyContextService::class)->pinCompany($this->company->id);
    $year = FiscalYear::create(['company_id' => $this->company->id, 'name' => 'FY', 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(), 'status' => 'OPEN']);
    FiscalPeriod::create(['fiscal_year_id' => $year->id, 'period_name' => 'Y', 'period_number' => 1, 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(), 'status' => 'OPEN']);

    $this->accounts = [
        'inventory' => Account::factory()->asset()->create(['company_id' => $this->company->id]),
        'adjustments' => Account::factory()->expense()->create(['company_id' => $this->company->id]),
        'cogs' => Account::factory()->expense()->create(['company_id' => $this->company->id]),
        'revenue' => Account::factory()->revenue()->create(['company_id' => $this->company->id]),
        'receivable' => Account::factory()->asset()->create(['company_id' => $this->company->id]),
    ];
    foreach ([AccountPurpose::Inventory->value => 'inventory', AccountPurpose::InventoryAdjustment->value => 'adjustments', AccountPurpose::CostOfGoodsSold->value => 'cogs'] as $purpose => $key) {
        AccountMapping::create(['company_id' => $this->company->id, 'purpose' => $purpose, 'account_id' => $this->accounts[$key]->id]);
    }

    $this->main = Warehouse::where('company_id', $this->company->id)->sole();
    $this->west = Warehouse::create(['company_id' => $this->company->id, 'code' => 'WEST', 'name' => 'West depot', 'status' => 'active']);
    $this->chair = Product::factory()->create(['company_id' => $this->company->id, 'sku' => 'CHAIR']);
    $this->storekeeper = companyUser(['inventory.stock.view', 'inventory.adjustments.create', 'inventory.adjustments.post', 'inventory.transfers.create', 'inventory.products.view'], $this->company);
});

function stockLedger(Account $account): string
{
    return (string) JournalLine::where('account_id', $account->id)->whereHas('journal', fn ($query) => $query->posted())
        ->get()->reduce(fn ($total, $line) => bcadd($total, bcsub($line->debit, $line->credit, 4), 4), '0');
}

/**
 * Post an adjustment through the screens: lines of [product, quantity change, unit cost] (or counted quantities for a count).
 */
function postAdjustment(string $reason, Warehouse $warehouse, array $lines): StockAdjustment
{
    $key = $reason === 'count' ? 'counted_quantity' : 'quantity';
    actingInCompany(test()->storekeeper, test()->company)->post(route('inventory.adjustments.store'), [
        'reason' => $reason, 'warehouse_id' => $warehouse->id, 'adjustment_date' => now()->toDateString(),
        'lines' => array_map(fn (array $line) => ['product_id' => $line[0]->id, $key => $line[1], 'unit_cost' => $line[2] ?? null], $lines),
    ])->assertSessionHasNoErrors();
    $adjustment = StockAdjustment::latest('id')->first();
    actingInCompany(test()->storekeeper, test()->company)->post(route('inventory.adjustments.post', $adjustment->id))->assertSessionHas('success');

    return $adjustment->fresh('lines');
}

test('receipts are averaged and issues leave at the weighted average cost', function () {
    postAdjustment('opening', $this->main, [[$this->chair, 10, 10]]);
    postAdjustment('opening', $this->main, [[$this->chair, 10, 20]]);

    expect($this->chair->fresh()->averageCost())->toBe('15.000000');

    $damage = postAdjustment('damage', $this->main, [[$this->chair, -4]]);

    expect($damage->lines->first()->value)->toBe('-60.0000')
        ->and($this->chair->fresh())->stock_quantity->toBe('16.0000')->stock_value->toBe('240.0000')
        ->and(stockLedger($this->accounts['inventory']))->toBe('240.0000')
        ->and(stockLedger($this->accounts['adjustments']))->toBe('-240.0000');
});

test('the last unit out takes whatever value remains, so no value is left behind by rounding', function () {
    postAdjustment('opening', $this->main, [[$this->chair, 3, '3.3333']]);
    $this->actingAs($this->storekeeper);
    $stock = app(StockService::class);

    $stock->issue($this->chair, $this->main->id, '1', now()->toDateString(), ['type' => 'test', 'id' => 1]);
    $stock->issue($this->chair, $this->main->id, '2', now()->toDateString(), ['type' => 'test', 'id' => 1]);

    expect($this->chair->fresh())->stock_quantity->toBe('0.0000')->stock_value->toBe('0.0000');
});

test('a stock count posts the difference between what was counted and what the books show', function () {
    postAdjustment('opening', $this->main, [[$this->chair, 15, 10]]);

    actingInCompany($this->storekeeper, $this->company)->get(route('inventory.adjustments.create', ['reason' => 'count', 'warehouse' => $this->main->id]))
        ->assertOk()->assertSee('CHAIR');
    $count = postAdjustment('count', $this->main, [[$this->chair, 12]]);

    expect($count->lines->first())->system_quantity->toBe('15.0000')->quantity->toBe('-3.0000')->value->toBe('-30.0000')
        ->and($this->chair->fresh()->stock_quantity)->toBe('12.0000');
});

test('stock cannot go below zero in a warehouse', function () {
    postAdjustment('opening', $this->main, [[$this->chair, 2, 10]]);

    actingInCompany($this->storekeeper, $this->company)->post(route('inventory.adjustments.store'), [
        'reason' => 'loss', 'warehouse_id' => $this->west->id, 'adjustment_date' => now()->toDateString(),
        'lines' => [['product_id' => $this->chair->id, 'quantity' => -1]],
    ]);
    $adjustment = StockAdjustment::latest('id')->first();

    actingInCompany($this->storekeeper, $this->company)->post(route('inventory.adjustments.post', $adjustment->id))
        ->assertSessionHas('error', 'Not enough CHAIR in WEST: 0 available, 1 needed.');
    expect($adjustment->fresh()->status)->toBe('DRAFT');
});

test('a transfer moves quantities between warehouses without changing value or posting a journal', function () {
    postAdjustment('opening', $this->main, [[$this->chair, 10, 10]]);
    $journals = Journal::count();

    actingInCompany($this->storekeeper, $this->company)->post(route('inventory.transfers.store'), [
        'from_warehouse_id' => $this->main->id, 'to_warehouse_id' => $this->west->id, 'transfer_date' => now()->toDateString(),
        'lines' => [['product_id' => $this->chair->id, 'quantity' => 4]],
    ])->assertRedirect();

    $balances = StockBalance::where('product_id', $this->chair->id)->pluck('quantity', 'warehouse_id');
    expect($balances[$this->main->id])->toBe('6.0000')->and($balances[$this->west->id])->toBe('4.0000')
        ->and($this->chair->fresh())->stock_quantity->toBe('10.0000')->stock_value->toBe('100.0000')
        ->and(Journal::count())->toBe($journals);

    actingInCompany($this->storekeeper, $this->company)->post(route('inventory.transfers.store'), [
        'from_warehouse_id' => $this->west->id, 'to_warehouse_id' => $this->main->id, 'transfer_date' => now()->toDateString(),
        'lines' => [['product_id' => $this->chair->id, 'quantity' => 5]],
    ])->assertSessionHas('error');
    expect(StockTransfer::count())->toBe(1);
});

test('posting a customer invoice for stock items issues the stock and posts its cost of goods sold', function () {
    postAdjustment('opening', $this->main, [[$this->chair, 10, 40]]);
    $customer = Customer::factory()->create(['company_id' => $this->company->id, 'receivable_account_id' => $this->accounts['receivable']->id]);
    $service = app(CustomerInvoiceService::class);
    $clerk = companyUser([], $this->company);
    $approver = companyUser([], $this->company);
    $invoiceFor = function (int $quantity) use ($service, $customer, $clerk) {
        $this->actingAs($clerk);

        return $service->createInvoice([
            'company_id' => $this->company->id, 'customer_id' => $customer->id, 'invoice_date' => now()->toDateString(),
            'lines' => [['product_id' => $this->chair->id, 'warehouse_id' => $this->main->id, 'account_id' => $this->accounts['revenue']->id, 'description' => 'Chairs', 'quantity' => $quantity, 'unit_price' => 100]],
        ]);
    };

    expect(fn () => $service->submitInvoice($invoiceFor(11)))->toThrow(InvalidAccountingTransactionException::class, 'Not enough CHAIR in stock: 10 available, 11 on the invoice.');

    $invoice = $service->submitInvoice($invoiceFor(3));
    $this->actingAs($approver);
    $posted = $service->postInvoice($service->approveInvoice($invoice));

    expect($posted->cost_journal_id)->not->toBeNull()
        ->and($posted->lines->first()->cost_value)->toBe('120.0000')
        ->and(stockLedger($this->accounts['cogs']))->toBe('120.0000')
        ->and(stockLedger($this->accounts['inventory']))->toBe('280.0000')
        ->and($this->chair->fresh()->stock_quantity)->toBe('7.0000')
        ->and(StockMove::where('source_type', StockMove::SOURCE_CUSTOMER_INVOICE)->value('journal_id'))->toBe($posted->cost_journal_id);
});

test('the stock screens show quantities, movements and the valuation', function () {
    postAdjustment('opening', $this->main, [[$this->chair, 10, 12.5]]);

    actingInCompany($this->storekeeper, $this->company)->get(route('inventory.stock.index'))->assertOk()->assertSee('CHAIR')->assertSee('125.00');
    actingInCompany($this->storekeeper, $this->company)->get(route('inventory.stock.movements', ['product' => $this->chair->id]))->assertOk()->assertSee('Stock adjustment');
    actingInCompany($this->storekeeper, $this->company)->get(route('inventory.stock.valuation', ['date' => now()->toDateString()]))->assertOk()->assertSee('125.00');
    actingInCompany($this->storekeeper, $this->company)->get(route('inventory.stock.valuation', ['date' => now()->subYear()->toDateString()]))->assertOk()->assertSee('No stock on this date.');
    actingInCompany($this->storekeeper, $this->company)->get(route('inventory.products.show', $this->chair->id))->assertOk()->assertSee('Latest stock movements');
});

test('a customer invoice line can be for a product delivered from a warehouse', function () {
    $customer = Customer::factory()->create(['company_id' => $this->company->id, 'receivable_account_id' => $this->accounts['receivable']->id]);
    $clerk = companyUser(['finance.customer-invoices.create', 'finance.customer-invoices.view'], $this->company);

    actingInCompany($clerk, $this->company)->get(route('finance.customer-invoices.create'))->assertOk()->assertSee('CHAIR')->assertSee('WEST');
    actingInCompany($clerk, $this->company)->post(route('finance.customer-invoices.store'), [
        'customer_id' => $customer->id, 'invoice_date' => now()->toDateString(),
        'lines' => [['product_id' => $this->chair->id, 'warehouse_id' => $this->west->id, 'account_id' => $this->accounts['revenue']->id, 'description' => 'Chair', 'quantity' => 1, 'unit_price' => 100]],
    ])->assertSessionHasNoErrors();

    expect(CustomerInvoice::sole()->lines->first())->product_id->toBe($this->chair->id)->warehouse_id->toBe($this->west->id);
});
