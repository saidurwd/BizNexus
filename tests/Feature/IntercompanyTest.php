<?php

use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Exceptions\UnauthorizedCompanyAccessException;
use Modules\Core\Models\Company;
use Modules\Core\Models\FiscalPeriod;
use Modules\Core\Models\FiscalYear;
use Modules\Core\Models\Tenant;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Enums\AccountPurpose;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\AccountMapping;
use Modules\Finance\Models\JournalLine;
use Modules\Finance\Services\IntercompanyService;

/**
 * @return array{receivable: Account, payable: Account, revenue: Account, expense: Account}
 */
function prepareGroupCompany(Company $company): array
{
    return app(CompanyContextService::class)->runAs($company->id, function () use ($company) {
        $year = FiscalYear::create(['company_id' => $company->id, 'name' => 'FY', 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(), 'status' => 'OPEN']);
        FiscalPeriod::create(['fiscal_year_id' => $year->id, 'period_name' => 'Y', 'period_number' => 1, 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear(), 'status' => 'OPEN']);

        $accounts = [
            'receivable' => Account::factory()->asset()->create(['company_id' => $company->id]),
            'payable' => Account::factory()->liability()->create(['company_id' => $company->id]),
            'revenue' => Account::factory()->revenue()->create(['company_id' => $company->id, 'account_code' => '4800']),
            'expense' => Account::factory()->expense()->create(['company_id' => $company->id, 'account_code' => '6800']),
        ];
        AccountMapping::create(['company_id' => $company->id, 'purpose' => AccountPurpose::IntercompanyReceivable, 'account_id' => $accounts['receivable']->id]);
        AccountMapping::create(['company_id' => $company->id, 'purpose' => AccountPurpose::IntercompanyPayable, 'account_id' => $accounts['payable']->id]);

        return $accounts;
    });
}

function balanceOf(Account $account): string
{
    return (string) JournalLine::withoutGlobalScopes()->where('account_id', $account->id)->get()
        ->reduce(fn ($total, $line) => bcadd($total, bcsub($line->debit, $line->credit, 4), 4), '0');
}

beforeEach(function () {
    $this->parent = Company::factory()->create();
    $this->subsidiary = Company::factory()->create(['base_currency_id' => $this->parent->base_currency_id]);
    $this->parentAccounts = prepareGroupCompany($this->parent);
    $this->subsidiaryAccounts = prepareGroupCompany($this->subsidiary);
    $this->user = companyUser([IntercompanyService::PERMISSION, 'finance.intercompany.view'], $this->parent);
    companyUserRole($this->user, $this->subsidiary, [IntercompanyService::PERMISSION]);
    $this->charge = fn (array $overrides = []) => [
        'transaction_date' => now()->toDateString(), 'currency_id' => $this->parent->base_currency_id, 'amount' => '1000',
        'description' => 'Management fee', 'source_account_id' => $this->parentAccounts['revenue']->id, 'target_account_code' => '6800', ...$overrides,
    ];
});

test('an intercompany charge is booked in both companies with the trading partner', function () {
    $this->actingAs($this->user);

    app(IntercompanyService::class)->charge($this->parent, $this->subsidiary, ($this->charge)());

    expect(balanceOf($this->parentAccounts['receivable']))->toEqual('1000.0000')
        ->and(balanceOf($this->parentAccounts['revenue']))->toEqual('-1000.0000')
        ->and(balanceOf($this->subsidiaryAccounts['expense']))->toEqual('1000.0000')
        ->and(balanceOf($this->subsidiaryAccounts['payable']))->toEqual('-1000.0000')
        ->and(JournalLine::withoutGlobalScopes()->where('company_id', $this->parent->id)->pluck('counterparty_company_id')->unique()->all())->toBe([$this->subsidiary->id]);
});

test('the initiator needs the intercompany permission in the other company', function () {
    $user = companyUser([IntercompanyService::PERMISSION], $this->parent);
    companyUserRole($user, $this->subsidiary, ['finance.journals.view']);
    $this->actingAs($user);

    app(IntercompanyService::class)->charge($this->parent, $this->subsidiary, ($this->charge)());
})->throws(UnauthorizedCompanyAccessException::class);

test('companies of different organisations cannot trade intercompany', function () {
    $this->actingAs($this->user);
    $outsider = Company::factory()->create(['tenant_id' => Tenant::factory()->create()->id]);

    app(IntercompanyService::class)->charge($this->parent, $outsider, ($this->charge)());
})->throws(InvalidAccountingTransactionException::class);

test('a charge is posted from the intercompany page', function () {
    actingInCompany($this->user, $this->parent)->get(route('finance.intercompany.index'))->assertOk()->assertSee($this->subsidiary->code);

    actingInCompany($this->user, $this->parent)
        ->post(route('finance.intercompany.store'), ($this->charge)(['target_company_id' => $this->subsidiary->id]))
        ->assertSessionHas('success');

    expect(balanceOf($this->subsidiaryAccounts['payable']))->toEqual('-1000.0000');
});
