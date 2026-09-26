<?php

use Carbon\Carbon;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Models\Company;
use Modules\Core\Models\Currency;
use Modules\Core\Models\FiscalPeriod;
use Modules\Core\Models\FiscalYear;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Enums\AccountPurpose;
use Modules\Finance\Enums\ExchangeRateType;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\AccountMapping;
use Modules\Finance\Models\JournalLine;
use Modules\Finance\Models\Supplier;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Finance\Services\ExchangeRateService;
use Modules\Finance\Services\SupplierInvoiceService;
use Modules\Inventory\Models\GoodsReceipt;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\PurchaseOrder;
use Modules\Inventory\Models\StockBalance;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\GoodsReceiptService;
use Modules\Inventory\Services\PurchaseInvoiceMatcher;
use Modules\Inventory\Services\ReorderService;

beforeEach(function () {
    $this->company = Company::factory()->create();
    app(CompanyContextService::class)->pinCompany($this->company->id);
    $year = FiscalYear::create(['company_id' => $this->company->id, 'name' => 'FY', 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(), 'status' => 'OPEN']);
    FiscalPeriod::create(['fiscal_year_id' => $year->id, 'period_name' => 'Y', 'period_number' => 1, 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(), 'status' => 'OPEN']);

    $this->accounts = [
        'inventory' => Account::factory()->asset()->create(['company_id' => $this->company->id]),
        'grni' => Account::factory()->liability()->create(['company_id' => $this->company->id]),
        'payable' => Account::factory()->liability()->create(['company_id' => $this->company->id]),
        'variance' => Account::factory()->expense()->create(['company_id' => $this->company->id]),
        'expense' => Account::factory()->expense()->create(['company_id' => $this->company->id]),
        'fxGain' => Account::factory()->revenue()->create(['company_id' => $this->company->id]),
        'fxLoss' => Account::factory()->expense()->create(['company_id' => $this->company->id]),
    ];
    foreach ([
        AccountPurpose::Inventory->value => 'inventory', AccountPurpose::GoodsReceivedNotInvoiced->value => 'grni', AccountPurpose::PurchasePriceVariance->value => 'variance',
        AccountPurpose::RealizedFxGain->value => 'fxGain', AccountPurpose::RealizedFxLoss->value => 'fxLoss', AccountPurpose::FxRounding->value => 'fxLoss',
    ] as $purpose => $key) {
        AccountMapping::create(['company_id' => $this->company->id, 'purpose' => $purpose, 'account_id' => $this->accounts[$key]->id]);
    }

    $this->supplier = Supplier::factory()->create(['company_id' => $this->company->id, 'payable_account_id' => $this->accounts['payable']->id]);
    $this->warehouse = Warehouse::where('company_id', $this->company->id)->sole();
    $this->chair = Product::factory()->create(['company_id' => $this->company->id, 'sku' => 'CHAIR', 'purchase_price' => 50]);
    $this->buyer = companyUser(['inventory.purchase-orders.view', 'inventory.purchase-orders.create', 'inventory.purchase-orders.submit', 'inventory.goods-receipts.create', 'inventory.goods-receipts.view', 'finance.supplier-invoices.create', 'finance.supplier-invoices.view', 'finance.supplier-invoices.submit'], $this->company);
    $this->manager = companyUser(['inventory.purchase-orders.view', 'inventory.purchase-orders.approve', 'finance.supplier-invoices.approve', 'finance.supplier-invoices.post'], $this->company);
});

function purchasingLedger(Account $account): string
{
    return (string) JournalLine::where('account_id', $account->id)->whereHas('journal', fn ($query) => $query->posted())
        ->get()->reduce(fn ($total, $line) => bcadd($total, bcsub($line->debit, $line->credit, 4), 4), '0');
}

/**
 * An approved order for the lines [product, quantity, unit price], in the company currency unless given.
 */
function approvedOrder(array $lines, ?int $currencyId = null): PurchaseOrder
{
    actingInCompany(test()->buyer, test()->company)->post(route('inventory.purchase-orders.store'), [
        'supplier_id' => test()->supplier->id, 'order_date' => now()->toDateString(), 'warehouse_id' => test()->warehouse->id, 'currency_id' => $currencyId,
        'lines' => array_map(fn (array $line) => ['product_id' => $line[0]->id, 'quantity' => $line[1], 'unit_price' => $line[2]], $lines),
    ])->assertSessionHasNoErrors();
    $order = PurchaseOrder::latest('id')->first();

    actingInCompany(test()->buyer, test()->company)->post(route('inventory.purchase-orders.submit', $order->id))->assertSessionHas('success');
    actingInCompany(test()->manager, test()->company)->post(route('inventory.purchase-orders.approve', $order->id))->assertSessionHas('success');

    return $order->fresh('lines');
}

function receiveGoods(PurchaseOrder $order, array $quantities, ?string $date = null): GoodsReceipt
{
    test()->actingAs(test()->buyer);

    return app(GoodsReceiptService::class)->receive($order, [
        'receipt_date' => $date ?? now()->toDateString(),
        'lines' => collect($quantities)->mapWithKeys(fn ($quantity, $index) => [$order->lines[$index]->id => $quantity])->all(),
    ]);
}

function postMatchedInvoice(PurchaseOrder $order, array $lines, ?string $date = null): SupplierInvoice
{
    test()->actingAs(test()->buyer);
    $invoice = app(PurchaseInvoiceMatcher::class)->createInvoice($order->fresh(), [
        'invoice_number' => 'INV-'.uniqid(), 'invoice_date' => $date ?? now()->toDateString(),
        'lines' => collect($lines)->mapWithKeys(fn (array $line, $index) => [$order->lines[$index]->id => ['quantity' => $line[0], 'unit_price' => $line[1]]])->all(),
    ]);
    $service = app(SupplierInvoiceService::class);
    $service->submitInvoice($invoice);
    test()->actingAs(test()->manager);

    return $service->postInvoice($service->approveInvoice($invoice->fresh()));
}

test('an order is drafted, approved by someone else, and cannot be approved by its creator', function () {
    $buyerWhoApproves = companyUser(['inventory.purchase-orders.view', 'inventory.purchase-orders.create', 'inventory.purchase-orders.submit', 'inventory.purchase-orders.approve'], $this->company);
    actingInCompany($buyerWhoApproves, $this->company)->post(route('inventory.purchase-orders.store'), [
        'supplier_id' => $this->supplier->id, 'order_date' => now()->toDateString(), 'warehouse_id' => $this->warehouse->id,
        'lines' => [['product_id' => $this->chair->id, 'quantity' => 10, 'unit_price' => 50]],
    ])->assertRedirect();
    $order = PurchaseOrder::sole();

    expect($order->order_number)->toStartWith('PO')->and($order->status)->toBe('DRAFT')->and($order->total_amount)->toBe('500.0000')
        ->and($order->lines->first()->description)->toBe($this->chair->name);

    actingInCompany($buyerWhoApproves, $this->company)->post(route('inventory.purchase-orders.submit', $order->id));
    actingInCompany($buyerWhoApproves, $this->company)->post(route('inventory.purchase-orders.approve', $order->id))->assertSessionHas('error', 'You cannot approve a purchase order you created.');
    actingInCompany($this->manager, $this->company)->post(route('inventory.purchase-orders.approve', $order->id))->assertSessionHas('success');

    expect($order->fresh())->status->toBe('APPROVED')->approved_by->toBe($this->manager->id);
    actingInCompany($this->manager, $this->company)->get(route('inventory.purchase-orders.show', $order->id))->assertOk()->assertSee($order->order_number)->assertSee('CHAIR');
});

test('receiving goods puts stock in the warehouse at the order price and credits goods received not invoiced', function () {
    $order = approvedOrder([[$this->chair, 10, 50]]);

    actingInCompany($this->buyer, $this->company)->post(route('inventory.goods-receipts.store', $order->id), [
        'receipt_date' => now()->toDateString(), 'warehouse_id' => $this->warehouse->id, 'delivery_note' => 'DN-1',
        'lines' => [$order->lines[0]->id => 4],
    ])->assertRedirect();

    $receipt = GoodsReceipt::sole();
    expect($this->chair->fresh())->stock_quantity->toBe('4.0000')->stock_value->toBe('200.0000')
        ->and(StockBalance::where('product_id', $this->chair->id)->value('quantity'))->toBe('4.0000')
        ->and(purchasingLedger($this->accounts['inventory']))->toBe('200.0000')
        ->and(purchasingLedger($this->accounts['grni']))->toBe('-200.0000')
        ->and($order->fresh()->status)->toBe('PARTIALLY_RECEIVED');

    actingInCompany($this->buyer, $this->company)->get(route('inventory.goods-receipts.show', $receipt->id))->assertOk()->assertSee('DN-1');

    expect(fn () => receiveGoods($order, [7]))->toThrow(InvalidAccountingTransactionException::class, 'Only 6 of CHAIR is still to be received');
    receiveGoods($order, [6]);
    expect($order->fresh()->status)->toBe('RECEIVED');
});

test('a matched invoice clears goods received not invoiced and posts the price difference as variance', function () {
    $order = approvedOrder([[$this->chair, 10, 50]]);
    receiveGoods($order, [10]);

    $invoice = postMatchedInvoice($order, [[10, '51.00']]);

    expect($invoice->status)->toBe('POSTED')
        ->and($invoice->purchase_order_id)->toBe($order->id)
        ->and(purchasingLedger($this->accounts['grni']))->toBe('0.0000')
        ->and(purchasingLedger($this->accounts['variance']))->toBe('10.0000')
        ->and(purchasingLedger($this->accounts['payable']))->toBe('-510.0000')
        ->and($order->fresh()->status)->toBe('CLOSED')
        ->and($order->lines[0]->fresh()->invoiced_quantity)->toBe('10.0000');
});

test('an invoice may not bill more than was received or stray beyond the price tolerance', function () {
    $order = approvedOrder([[$this->chair, 10, 50]]);
    receiveGoods($order, [4]);
    $this->actingAs($this->buyer);
    $matcher = app(PurchaseInvoiceMatcher::class);

    expect(fn () => $matcher->createInvoice($order, ['invoice_number' => 'A', 'invoice_date' => now()->toDateString(), 'lines' => [$order->lines[0]->id => ['quantity' => 5, 'unit_price' => 50]]]))
        ->toThrow(InvalidAccountingTransactionException::class, 'CHAIR: the invoice bills 5 but only 4 has been received and not yet invoiced');
    expect(fn () => $matcher->createInvoice($order, ['invoice_number' => 'B', 'invoice_date' => now()->toDateString(), 'lines' => [$order->lines[0]->id => ['quantity' => 4, 'unit_price' => 60]]]))
        ->toThrow(InvalidAccountingTransactionException::class, 'differs from the order price 50 by more than 5%');

    $first = $matcher->createInvoice($order, ['invoice_number' => 'C', 'invoice_date' => now()->toDateString(), 'lines' => [$order->lines[0]->id => ['quantity' => 4, 'unit_price' => 50]]]);
    app(SupplierInvoiceService::class)->submitInvoice($first);

    expect(fn () => $matcher->createInvoice($order, ['invoice_number' => 'D', 'invoice_date' => now()->toDateString(), 'lines' => [$order->lines[0]->id => ['quantity' => 1, 'unit_price' => 50]]]))
        ->toThrow(InvalidAccountingTransactionException::class, 'only 0 has been received');
    expect(SupplierInvoice::pluck('invoice_number')->all())->toBe(['C']);
});

test('a foreign-currency order clears goods received at the receipt rate and books the rate change as exchange difference', function () {
    $usd = Currency::firstOrCreate(['code' => 'EUR'], ['name' => 'Euro', 'symbol' => '€', 'decimal_places' => 2, 'status' => 'active']);
    $rates = app(ExchangeRateService::class);
    $rates->record($this->company, $usd, Carbon::parse('2026-01-10'), '1.2', ExchangeRateType::Spot);
    $rates->record($this->company, $usd, Carbon::parse('2026-01-20'), '1.1', ExchangeRateType::Spot);
    $this->travelTo(Carbon::parse('2026-01-25'));

    $order = approvedOrder([[$this->chair, 10, 100]], $usd->id);
    receiveGoods($order, [10], '2026-01-10');
    expect($this->chair->fresh()->stock_value)->toBe('1200.0000');

    postMatchedInvoice($order, [[10, 100]], '2026-01-20');

    expect(purchasingLedger($this->accounts['grni']))->toBe('0.0000')
        ->and(purchasingLedger($this->accounts['payable']))->toBe('-1100.0000')
        ->and(purchasingLedger($this->accounts['fxGain']))->toBe('-100.0000')
        ->and(purchasingLedger($this->accounts['variance']))->toBe('0');
});

test('non-stock items are expensed when received and never enter stock', function () {
    $service = Product::factory()->nonStock()->create(['company_id' => $this->company->id, 'expense_account_id' => $this->accounts['expense']->id]);
    $order = approvedOrder([[$service, 3, 20]]);

    receiveGoods($order, [3]);

    expect(purchasingLedger($this->accounts['expense']))->toBe('60.0000')
        ->and(purchasingLedger($this->accounts['grni']))->toBe('-60.0000')
        ->and($service->fresh()->stock_quantity)->toBe('0.0000')
        ->and(StockBalance::count())->toBe(0);
});

test('a posted order line keeps its product and a received order cannot be cancelled', function () {
    $order = approvedOrder([[$this->chair, 2, 50]]);
    receiveGoods($order, [1]);

    actingInCompany(companyUser(['inventory.products.manage'], $this->company), $this->company)->delete(route('inventory.products.destroy', $this->chair->id))->assertSessionHas('error');
    actingInCompany(companyUser(['inventory.purchase-orders.cancel'], $this->company), $this->company)->post(route('inventory.purchase-orders.cancel', $order->id))->assertSessionHas('error');

    expect(Product::whereKey($this->chair->id)->exists())->toBeTrue()->and($order->fresh()->status)->toBe('PARTIALLY_RECEIVED');
});

test('the invoice form on an order records a draft supplier invoice for what was received', function () {
    $order = approvedOrder([[$this->chair, 10, 50]]);
    receiveGoods($order, [6]);

    actingInCompany($this->buyer, $this->company)->get(route('inventory.purchase-orders.invoice.create', $order->id))->assertOk()->assertSee('CHAIR');
    actingInCompany($this->buyer, $this->company)->post(route('inventory.purchase-orders.invoice.store', $order->id), [
        'invoice_number' => 'SUP-77', 'invoice_date' => now()->toDateString(),
        'lines' => [$order->lines[0]->id => ['quantity' => 6, 'unit_price' => 50]],
    ])->assertRedirect();

    $invoice = SupplierInvoice::with('lines')->sole();
    expect($invoice)->invoice_number->toBe('SUP-77')->status->toBe('DRAFT')->total_amount->toBe('300.0000')
        ->and($invoice->lines->first())->purchase_order_line_id->toBe($order->lines[0]->id)->account_id->toBe($this->accounts['grni']->id);

    actingInCompany(companyUser(['finance.supplier-invoices.update'], $this->company), $this->company)->get(route('finance.supplier-invoices.edit', $invoice->id))->assertRedirect(route('finance.supplier-invoices.show', $invoice->id));
    actingInCompany($this->buyer, $this->company)->get(route('finance.supplier-invoices.show', $invoice->id))->assertOk()->assertSee($order->order_number);
});

test('products at or below their reorder level become draft purchase orders per supplier', function () {
    $this->chair->update(['reorder_level' => 10, 'reorder_quantity' => 20, 'preferred_supplier_id' => $this->supplier->id]);
    $desk = Product::factory()->create(['company_id' => $this->company->id, 'sku' => 'DESK', 'purchase_price' => 200, 'reorder_level' => 2]);
    $lamp = Product::factory()->create(['company_id' => $this->company->id, 'sku' => 'LAMP', 'reorder_level' => 1]);
    $lamp->forceFill(['stock_quantity' => 5, 'stock_value' => 50])->save();
    approvedOrder([[$this->chair, 4, 50]]);
    $otherSupplier = Supplier::factory()->create(['company_id' => $this->company->id]);
    $planner = companyUser(['inventory.purchase-orders.create', 'inventory.purchase-orders.view'], $this->company);

    $rows = app(ReorderService::class)->suggestions()->keyBy(fn ($row) => $row['product']->sku);
    expect($rows->keys()->all())->toBe(['CHAIR', 'DESK'])
        ->and($rows['CHAIR'])->on_order->toBe('4.0000')->projected->toBe('4.0000')->suggested->toBe('20.0000')
        ->and($rows['DESK']['suggested'])->toBe('2.0000');

    actingInCompany($planner, $this->company)->get(route('inventory.reorder.index'))->assertOk()->assertSee('CHAIR')->assertDontSee('LAMP');
    actingInCompany($planner, $this->company)->post(route('inventory.reorder.store'), [
        'warehouse_id' => $this->warehouse->id,
        'lines' => [
            $this->chair->id => ['selected' => 1, 'quantity' => 20, 'supplier_id' => $this->supplier->id],
            $desk->id => ['selected' => 1, 'quantity' => 3, 'supplier_id' => $otherSupplier->id],
        ],
    ])->assertRedirect()->assertSessionHas('success');

    $drafts = PurchaseOrder::with('lines')->where('status', 'DRAFT')->get()->keyBy('supplier_id');
    expect($drafts)->toHaveCount(2)
        ->and($drafts[$this->supplier->id]->lines->first())->product_id->toBe($this->chair->id)->quantity->toBe('20.0000')->unit_price->toBe('50.0000')
        ->and($drafts[$otherSupplier->id]->total_amount)->toBe('600.0000');
});
