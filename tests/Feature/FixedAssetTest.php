<?php

use Carbon\CarbonImmutable;
use Modules\Assets\Models\AssetCategory;
use Modules\Assets\Models\AssetDepreciationRun;
use Modules\Assets\Models\FixedAsset;
use Modules\Assets\Services\AssetReportService;
use Modules\Assets\Services\DepreciationService;
use Modules\Assets\Services\FixedAssetService;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Models\Company;
use Modules\Core\Models\FiscalPeriod;
use Modules\Core\Models\FiscalYear;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\JournalLine;
use Modules\Finance\Models\Supplier;
use Modules\Finance\Services\SupplierInvoiceService;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-06-15'));
    $this->company = Company::factory()->create();
    app(CompanyContextService::class)->pinCompany($this->company->id);
    $year = FiscalYear::create(['company_id' => $this->company->id, 'name' => 'FY2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'OPEN']);
    FiscalPeriod::create(['fiscal_year_id' => $year->id, 'period_name' => '2026', 'period_number' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'OPEN']);

    $this->accounts = [
        'cost' => Account::factory()->asset()->create(['company_id' => $this->company->id]),
        'accumulated' => Account::factory()->asset()->create(['company_id' => $this->company->id]),
        'expense' => Account::factory()->expense()->create(['company_id' => $this->company->id]),
        'disposal' => Account::factory()->expense()->create(['company_id' => $this->company->id]),
        'bank' => Account::factory()->asset()->create(['company_id' => $this->company->id]),
        'payable' => Account::factory()->liability()->create(['company_id' => $this->company->id]),
    ];
    $this->category = AssetCategory::create([
        'company_id' => $this->company->id, 'code' => 'EQUIP', 'name' => 'Equipment',
        'asset_account_id' => $this->accounts['cost']->id, 'accumulated_depreciation_account_id' => $this->accounts['accumulated']->id,
        'depreciation_expense_account_id' => $this->accounts['expense']->id, 'disposal_account_id' => $this->accounts['disposal']->id,
        'depreciation_method' => 'straight_line', 'useful_life_months' => 12, 'status' => 'active',
    ]);
    $this->accountant = companyUser(['assets.view', 'assets.manage', 'assets.dispose', 'assets.depreciate', 'assets.setup'], $this->company);
    $this->actingAs($this->accountant);
});

function assetLedger(Account $account): string
{
    return (string) JournalLine::where('account_id', $account->id)->whereHas('journal', fn ($query) => $query->posted())
        ->get()->reduce(fn ($total, $line) => bcadd($total, bcsub($line->debit, $line->credit, 4), 4), '0');
}

function registerAsset(array $overrides = []): FixedAsset
{
    return app(FixedAssetService::class)->register([
        'company_id' => test()->company->id, 'name' => 'Laptop', 'category_id' => test()->category->id,
        'acquisition_date' => '2026-01-10', 'cost' => 1200, 'offset_account_id' => test()->accounts['bank']->id, ...$overrides,
    ]);
}

test('registering an asset posts its cost against the chosen account', function () {
    actingInCompany($this->accountant, $this->company)->post(route('assets.assets.store'), [
        'name' => 'Laptop', 'category_id' => $this->category->id, 'acquisition_date' => '2026-01-10', 'cost' => 1200, 'offset_account_id' => $this->accounts['bank']->id,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $asset = FixedAsset::sole();
    expect($asset)->asset_number->toStartWith('FA')->useful_life_months->toBe(12)->depreciation_method->toBe('straight_line')
        ->and(assetLedger($this->accounts['cost']))->toBe('1200.0000')
        ->and(assetLedger($this->accounts['bank']))->toBe('-1200.0000');
});

test('a run catches up the months since the asset went into service and posts one journal', function () {
    $asset = registerAsset();
    $depreciation = app(DepreciationService::class);

    expect($depreciation->preview(CarbonImmutable::parse('2026-03-31'))->first()['amount'])->toBe('300.00');

    $run = $depreciation->run(CarbonImmutable::parse('2026-03-01'));

    expect($run)->total_amount->toBe('300.0000')->asset_count->toBe(1)
        ->and($asset->fresh())->accumulated_depreciation->toBe('300.0000')->months_depreciated->toBe(3)
        ->and($asset->fresh()->depreciated_until->toDateString())->toBe('2026-03-31')
        ->and(assetLedger($this->accounts['expense']))->toBe('300.0000')
        ->and(assetLedger($this->accounts['accumulated']))->toBe('-300.0000');

    expect(fn () => $depreciation->run(CarbonImmutable::parse('2026-03-31')))->toThrow(InvalidAccountingTransactionException::class, 'already been posted');
});

test('the latest run can be reversed, putting the assets back', function () {
    $asset = registerAsset();
    $depreciation = app(DepreciationService::class);
    $depreciation->run(CarbonImmutable::parse('2026-02-28'));
    $second = $depreciation->run(CarbonImmutable::parse('2026-03-31'));

    $depreciation->reverse($second, 'Wrong month');

    expect($second->fresh()->status)->toBe(AssetDepreciationRun::STATUS_REVERSED)
        ->and($asset->fresh())->accumulated_depreciation->toBe('200.0000')->months_depreciated->toBe(2)
        ->and(assetLedger($this->accounts['expense']))->toBe('200.0000');
    expect(fn () => $depreciation->reverse(AssetDepreciationRun::where('status', 'POSTED')->sole()->fresh()))->not->toThrow(Exception::class);
});

test('declining balance applies the annual rate to the carrying amount each month', function () {
    $asset = registerAsset(['cost' => 1000, 'depreciation_method' => 'declining_balance', 'declining_rate' => 24, 'useful_life_months' => 60]);

    app(DepreciationService::class)->run(CarbonImmutable::parse('2026-02-28'));

    expect($asset->fresh()->accumulated_depreciation)->toBe('39.6000');
});

test('an asset taken over with depreciation already charged continues over its remaining life', function () {
    $asset = registerAsset(['acquisition_date' => '2025-12-01', 'offset_account_id' => null, 'opening_accumulated_depreciation' => 600, 'depreciated_until' => '2026-05']);

    expect($asset)->months_depreciated->toBe(6)->and($asset->nextMonthDepreciation())->toBe('100.00000000')
        ->and(assetLedger($this->accounts['cost']))->toBe('0');

    app(DepreciationService::class)->run(CarbonImmutable::parse('2026-06-30'));
    expect($asset->fresh()->accumulated_depreciation)->toBe('700.0000');
});

test('an impairment lowers the carrying amount and later depreciation spreads what is left', function () {
    $asset = registerAsset();
    app(DepreciationService::class)->run(CarbonImmutable::parse('2026-02-28'));

    app(FixedAssetService::class)->impair($asset, '2026-02-28', '400', 'Damaged');

    expect($asset->fresh()->bookValue())->toBe('600.0000')
        ->and($asset->fresh()->nextMonthDepreciation())->toBe('60.00000000')
        ->and(assetLedger($this->accounts['expense']))->toBe('600.0000');
    expect(fn () => app(FixedAssetService::class)->impair($asset->fresh(), '2026-02-28', '700', null))->toThrow(InvalidAccountingTransactionException::class);
});

test('a sale charges depreciation to the month before, removes the asset and posts the gain', function () {
    $asset = registerAsset();

    actingInCompany($this->accountant, $this->company)->post(route('assets.assets.dispose', $asset->id), [
        'disposal_date' => '2026-04-10', 'proceeds' => 1000, 'proceeds_account_id' => $this->accounts['bank']->id,
    ])->assertSessionHas('success');

    expect($asset->fresh())->status->toBe('DISPOSED')->accumulated_depreciation->toBe('300.0000')
        ->and(assetLedger($this->accounts['cost']))->toBe('0.0000')
        ->and(assetLedger($this->accounts['accumulated']))->toBe('0.0000')
        ->and(assetLedger($this->accounts['disposal']))->toBe('-100.0000')
        ->and(assetLedger($this->accounts['bank']))->toBe('-200.0000');
});

test('a purchase posted to an asset account is capitalised without a second posting', function () {
    $supplier = Supplier::factory()->create(['company_id' => $this->company->id, 'payable_account_id' => $this->accounts['payable']->id]);
    $invoices = app(SupplierInvoiceService::class);
    $invoice = $invoices->submitInvoice($invoices->createInvoice([
        'company_id' => $this->company->id, 'supplier_id' => $supplier->id, 'invoice_number' => 'SUP-1', 'invoice_date' => '2026-05-05',
        'lines' => [['account_id' => $this->accounts['cost']->id, 'description' => 'Forklift', 'quantity' => 1, 'unit_price' => 2400]],
    ]));
    $this->actingAs(companyUser([], $this->company));
    $invoices->postInvoice($invoices->approveInvoice($invoice));
    $this->actingAs($this->accountant);

    actingInCompany($this->accountant, $this->company)->get(route('assets.capitalise.index'))->assertOk()->assertSee('Forklift');
    $line = app(FixedAssetService::class)->capitalisableLines()->sole();
    actingInCompany($this->accountant, $this->company)->post(route('assets.capitalise.store'), [
        'source_type' => $line['source_type'], 'source_id' => $line['source_id'], 'category_id' => $this->category->id,
    ])->assertRedirect();

    expect(FixedAsset::sole())->cost->toBe('2400.0000')->supplier_id->toBe($supplier->id)
        ->and(assetLedger($this->accounts['cost']))->toBe('2400.0000')
        ->and(app(FixedAssetService::class)->capitalisableLines())->toBeEmpty();
});

test('the movement schedule reconciles cost and depreciation by category', function () {
    registerAsset();
    $sold = registerAsset(['name' => 'Printer', 'cost' => 600]);
    app(DepreciationService::class)->run(CarbonImmutable::parse('2026-02-28'));
    app(FixedAssetService::class)->dispose($sold, '2026-03-15', '0', null, null);

    $row = app(AssetReportService::class)->movements(CarbonImmutable::parse('2026-01-01'), CarbonImmutable::parse('2026-03-31'))->sole();

    expect($row)->additions->toBe('1800.0000')->disposals->toBe('600.0000')->cost_closing->toBe('1200.0000')
        ->depreciation->toBe('300.0000')->depreciation_disposed->toBe('100.0000')->depreciation_closing->toBe('200.0000')
        ->book_value_closing->toBe('1000.0000');
    expect(app(AssetReportService::class)->register(CarbonImmutable::parse('2026-03-31')))->toHaveCount(1);
});

test('the fixed asset screens render', function () {
    $asset = registerAsset();
    app(DepreciationService::class)->run(CarbonImmutable::parse('2026-01-31'));

    foreach ([
        route('assets.assets.index'), route('assets.assets.show', $asset->id), route('assets.assets.create'), route('assets.assets.edit', $asset->id),
        route('assets.depreciation.index', ['period' => '2026-05']), route('assets.depreciation.show', AssetDepreciationRun::sole()->id),
        route('assets.categories.index'), route('assets.reports.register'), route('assets.reports.movements'), route('assets.reports.forecast'),
    ] as $url) {
        actingInCompany($this->accountant, $this->company)->get($url)->assertOk();
    }

    actingInCompany($this->accountant, $this->company)->get(route('assets.reports.forecast'))->assertSee('100.00');
});

test('the depreciation command posts last month for each company', function () {
    registerAsset();

    $this->artisan('assets:depreciate', ['month' => '2026-02', '--company' => [$this->company->code]])->assertSuccessful();
    $this->artisan('assets:depreciate', ['month' => '2026-02', '--company' => [$this->company->code]])->expectsOutputToContain('nothing to depreciate')->assertSuccessful();

    expect(AssetDepreciationRun::sole())->total_amount->toBe('200.0000');
});
