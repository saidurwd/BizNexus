<?php

use Modules\Core\Models\Company;
use Modules\Core\Models\FiscalPeriod;
use Modules\Core\Models\FiscalYear;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\Supplier;
use Modules\Finance\Services\JournalService;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\PurchaseOrderService;

beforeEach(function () {
    $this->company = Company::factory()->create(['name' => 'Acme Trading Ltd']);
    app(CompanyContextService::class)->pinCompany($this->company->id);
});

test('every document and report screen can be printed', function () {
    $screens = [
        'finance/journals/show', 'finance/receipts/show', 'finance/payments/show', 'finance/supplier-invoices/show', 'finance/customer-invoices/show',
        'finance/customer-credit-notes/show', 'finance/supplier-credit-notes/show', 'finance/budgets/show', 'finance/customer-statements/show',
        'finance/supplier-statements/show', 'finance/consolidation/show', 'inventory/purchase-orders/show', 'inventory/goods-receipts/show',
        'inventory/supplier-returns/show', 'inventory/adjustments/show', 'inventory/transfers/show', 'inventory/stock/index', 'inventory/stock/movements',
        'inventory/stock/valuation', 'sales/deliveries/show', 'sales/orders/show', 'sales/quotations/show', 'assets/assets/show', 'assets/depreciation/show',
        ...collect(File::files(resource_path('views/finance/reports')))->map(fn ($file) => 'finance/reports/'.str_replace('.blade.php', '', $file->getFilename()))->all(),
        ...collect(File::files(resource_path('views/assets/reports')))->map(fn ($file) => 'assets/reports/'.str_replace('.blade.php', '', $file->getFilename()))->all(),
    ];

    $withoutPrinting = collect($screens)->reject(function (string $view) {
        $source = File::get(resource_path("views/{$view}.blade.php"));

        return str_contains($source, '<x-print-toolbar') || str_contains($source, 'window.print()');
    });

    expect($withoutPrinting->values()->all())->toBe([]);
});

test('a journal prints as a voucher on the company letterhead with signature lines', function () {
    $year = FiscalYear::create(['company_id' => $this->company->id, 'name' => 'FY', 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(), 'status' => 'OPEN']);
    FiscalPeriod::create(['fiscal_year_id' => $year->id, 'period_name' => 'Y', 'period_number' => 1, 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(), 'status' => 'OPEN']);
    $user = companyUser(['finance.journals.view', 'finance.journals.create'], $this->company);
    $this->actingAs($user);
    $journal = app(JournalService::class)->create([
        'journal_date' => now()->toDateString(), 'description' => 'Office rent',
        'lines' => [
            ['account_id' => Account::factory()->expense()->create(['company_id' => $this->company->id])->id, 'debit' => 500],
            ['account_id' => Account::factory()->asset()->create(['company_id' => $this->company->id])->id, 'credit' => 500],
        ],
    ]);

    actingInCompany($user, $this->company)->get(route('finance.journals.show', $journal->id))
        ->assertOk()
        ->assertSee('window.print()', false)
        ->assertSeeInOrder(['report-letterhead', 'Acme Trading Ltd', 'Journal voucher', $journal->journal_number])
        ->assertSeeInOrder(['Prepared by', 'Checked by', 'Approved by']);
});

test('a purchase order can be downloaded as a PDF to send to the supplier', function () {
    $user = companyUser(['inventory.purchase-orders.view', 'inventory.purchase-orders.create'], $this->company);
    $this->actingAs($user);
    $order = app(PurchaseOrderService::class)->create([
        'company_id' => $this->company->id,
        'supplier_id' => Supplier::factory()->create(['company_id' => $this->company->id])->id,
        'order_date' => now()->toDateString(),
        'warehouse_id' => Warehouse::where('company_id', $this->company->id)->value('id'),
        'lines' => [['product_id' => Product::factory()->create(['company_id' => $this->company->id])->id, 'quantity' => 2, 'unit_price' => 50]],
    ]);

    actingInCompany($user, $this->company)->get(route('inventory.purchase-orders.pdf', $order->id))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
    actingInCompany($user, $this->company)->get(route('inventory.purchase-orders.show', $order->id))
        ->assertSee(route('inventory.purchase-orders.pdf', $order->id));
});
