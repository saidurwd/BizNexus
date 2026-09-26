<?php

use Illuminate\Support\Carbon;
use Modules\Core\Models\Company;
use Modules\Core\Models\Currency;
use Modules\Core\Models\FiscalPeriod;
use Modules\Core\Models\FiscalYear;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Support\Money;
use Modules\Finance\Enums\AccountPurpose;
use Modules\Finance\Enums\ExchangeRateType;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\AccountMapping;
use Modules\Finance\Models\ConsolidationGroup;
use Modules\Finance\Services\ConsolidationService;
use Modules\Finance\Services\ExchangeRateService;
use Modules\Finance\Services\IntercompanyService;
use Modules\Finance\Services\JournalService;

function consolidationMember(Company $company): array
{
    return app(CompanyContextService::class)->runAs($company->id, function () use ($company) {
        $year = FiscalYear::create(['company_id' => $company->id, 'name' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'OPEN']);
        FiscalPeriod::create(['fiscal_year_id' => $year->id, 'period_name' => '2026', 'period_number' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'OPEN']);

        $accounts = [
            'cash' => Account::factory()->asset()->create(['company_id' => $company->id, 'account_code' => '1110']),
            'receivable' => Account::factory()->asset()->create(['company_id' => $company->id, 'account_code' => '1310']),
            'payable' => Account::factory()->liability()->create(['company_id' => $company->id, 'account_code' => '2310']),
            'revenue' => Account::factory()->revenue()->create(['company_id' => $company->id, 'account_code' => '4800']),
            'expense' => Account::factory()->expense()->create(['company_id' => $company->id, 'account_code' => '6800']),
        ];
        AccountMapping::create(['company_id' => $company->id, 'purpose' => AccountPurpose::IntercompanyReceivable, 'account_id' => $accounts['receivable']->id]);
        AccountMapping::create(['company_id' => $company->id, 'purpose' => AccountPurpose::IntercompanyPayable, 'account_id' => $accounts['payable']->id]);

        return $accounts;
    });
}

beforeEach(function () {
    $eur = Currency::create(['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'decimal_places' => 2, 'status' => 'active']);
    $this->parent = Company::factory()->create();
    $this->subsidiary = Company::factory()->create(['base_currency_id' => $eur->id]);
    $parentAccounts = consolidationMember($this->parent);
    $subsidiaryAccounts = consolidationMember($this->subsidiary);

    $rates = app(ExchangeRateService::class);
    $rates->record($this->subsidiary, $this->parent->baseCurrency, Carbon::parse('2026-06-01'), '0.9');
    $rates->record($this->parent, $eur, Carbon::parse('2026-06-30'), '1.2', ExchangeRateType::Closing);
    $rates->record($this->parent, $eur, Carbon::parse('2026-06-30'), '1.1', ExchangeRateType::Average);

    $this->user = companyUser([IntercompanyService::PERMISSION, 'finance.consolidation.view', 'finance.consolidation.manage'], $this->parent);
    companyUserRole($this->user, $this->subsidiary, [IntercompanyService::PERMISSION, 'finance.consolidation.view']);
    $this->actingAs($this->user);

    app(IntercompanyService::class)->charge($this->parent, $this->subsidiary, [
        'transaction_date' => '2026-06-01', 'currency_id' => $this->parent->base_currency_id, 'amount' => '1000',
        'description' => 'Management fee', 'source_account_id' => $parentAccounts['revenue']->id, 'target_account_code' => '6800',
    ]);
    app(CompanyContextService::class)->runAs($this->subsidiary->id, fn () => app(JournalService::class)->postFromSource([
        'journal_date' => '2026-06-15',
        'lines' => [['account_id' => $subsidiaryAccounts['cash']->id, 'debit' => '5000'], ['account_id' => $subsidiaryAccounts['revenue']->id, 'credit' => '5000']],
    ]));

    $this->group = ConsolidationGroup::create(['tenant_id' => $this->parent->tenant_id, 'parent_company_id' => $this->parent->id, 'name' => 'Group']);
    $this->group->members()->attach([$this->parent->id => ['ownership_percent' => 100], $this->subsidiary->id => ['ownership_percent' => 80]]);
});

test('intercompany balances are eliminated and the translation reserve balances the group', function () {
    $report = app(ConsolidationService::class)->trialBalance($this->group, Carbon::parse('2026-06-30'));
    $rows = $report['rows']->keyBy('account_code');

    expect($rows['1310']['consolidated']->isZero())->toBeTrue()
        ->and($rows['2310']['consolidated']->isZero())->toBeTrue()
        ->and($rows['6800']['consolidated']->isZero())->toBeTrue()
        ->and($rows['1110']['consolidated']->amount)->toBe('6000.00')
        ->and($rows['4800']['consolidated']->amount)->toBe('-5500.00')
        ->and($report['rows']->reduce(fn (Money $total, array $row) => $total->plus($row['consolidated']), Money::zero('USD'))->plus($report['translation_difference'])->isZero())->toBeTrue();
});

test('non-controlling interests take their share of the subsidiary', function () {
    $report = app(ConsolidationService::class)->trialBalance($this->group, Carbon::parse('2026-06-30'));

    expect($report['nci_profit']->amount)->toBe('902.00')
        ->and($report['net_profit']->amount)->toBe('5500.00');
});

test('the consolidated report requires the permission in every member', function () {
    actingInCompany($this->user, $this->parent)->get(route('finance.consolidation.show', ['id' => $this->group->id, 'as_of' => '2026-06-30']))
        ->assertOk()->assertSee('Consolidated Trial Balance')->assertSee('902.00');

    $limited = companyUser(['finance.consolidation.view'], $this->parent);
    actingInCompany($limited, $this->parent)->get(route('finance.consolidation.show', $this->group->id))->assertForbidden();
});

test('a group is created with the parent at 100 percent', function () {
    actingInCompany($this->user, $this->parent)->post(route('finance.consolidation.store'), [
        'name' => 'Holding', 'members' => [['company_id' => $this->subsidiary->id, 'ownership_percent' => 60]],
    ])->assertSessionHasNoErrors();

    $group = ConsolidationGroup::where('name', 'Holding')->sole();
    expect($group->members()->pluck('ownership_percent', 'companies.id')->map(fn ($percent) => (float) $percent)->all())
        ->toBe([$this->parent->id => 100.0, $this->subsidiary->id => 60.0]);
});
