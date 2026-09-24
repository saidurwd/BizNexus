<?php

use Modules\Core\Models\Company;
use Modules\Core\Models\Currency;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Enums\AccountPurpose;
use Modules\Finance\Exceptions\MissingAccountMappingException;
use Modules\Finance\Exceptions\MissingExchangeRateException;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\AccountMapping;
use Modules\Finance\Models\JournalLine;
use Modules\Finance\Services\ExchangeRateService;
use Modules\Finance\Services\JournalService;

beforeEach(function () {
    $this->company = Company::factory()->create();
    app(CompanyContextService::class)->pinCompany($this->company->id);
    $this->eur = Currency::create(['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'decimal_places' => 2, 'status' => 'active']);
    $this->jpy = Currency::create(['code' => 'JPY', 'name' => 'Yen', 'symbol' => '¥', 'decimal_places' => 0, 'status' => 'active']);
    $this->receivable = Account::factory()->asset()->create(['company_id' => $this->company->id]);
    $this->revenue = Account::factory()->revenue()->create(['company_id' => $this->company->id]);
    $this->otherRevenue = Account::factory()->revenue()->create(['company_id' => $this->company->id]);
    $this->rounding = Account::factory()->expense()->create(['company_id' => $this->company->id]);
    $this->journals = app(JournalService::class);
});

function foreignJournal(Currency $currency, array $lines, ?string $rate = null)
{
    return test()->journals->create(array_filter([
        'journal_date' => now()->toDateString(),
        'currency_id' => $currency->id,
        'exchange_rate' => $rate,
        'lines' => $lines,
    ], fn ($value) => $value !== null));
}

test('functional amounts are computed from transaction amounts at the journal rate', function () {
    $journal = foreignJournal($this->eur, [
        ['account_id' => $this->receivable->id, 'debit' => 1000],
        ['account_id' => $this->revenue->id, 'credit' => 1000],
    ], '1.08765');

    $debitLine = $journal->lines()->where('account_id', $this->receivable->id)->sole();
    expect($debitLine->currency_debit)->toEqual('1000.0000')
        ->and($debitLine->debit)->toEqual('1087.6500')
        ->and($journal->fresh()->total_debit)->toEqual('1087.6500');
});

test('the spot rate of the journal date is used when no rate is given', function () {
    app(ExchangeRateService::class)->record($this->company, $this->eur, now(), '1.10');

    $journal = foreignJournal($this->eur, [
        ['account_id' => $this->receivable->id, 'debit' => 50],
        ['account_id' => $this->revenue->id, 'credit' => 50],
    ]);

    expect($journal->exchange_rate)->toEqual('1.10000000')
        ->and($journal->fresh()->total_debit)->toEqual('55.0000');
});

test('a foreign-currency journal without a rate is refused', function () {
    foreignJournal($this->eur, [
        ['account_id' => $this->receivable->id, 'debit' => 50],
        ['account_id' => $this->revenue->id, 'credit' => 50],
    ]);
})->throws(MissingExchangeRateException::class);

test('conversion rounding differences are posted to the FX rounding account', function () {
    AccountMapping::create(['company_id' => $this->company->id, 'purpose' => AccountPurpose::FxRounding, 'account_id' => $this->rounding->id]);

    $journal = foreignJournal($this->eur, [
        ['account_id' => $this->receivable->id, 'debit' => 10],
        ['account_id' => $this->revenue->id, 'credit' => 5],
        ['account_id' => $this->otherRevenue->id, 'credit' => 5],
    ], '1.005');

    $roundingLine = $journal->lines()->where('line_type', JournalLine::TYPE_FX_ROUNDING)->sole();
    expect($roundingLine->account_id)->toBe($this->rounding->id)
        ->and($roundingLine->debit)->toEqual('0.0100')
        ->and($journal->fresh()->isBalanced())->toBeTrue();
});

test('a rounding difference without a mapped rounding account is refused', function () {
    foreignJournal($this->eur, [
        ['account_id' => $this->receivable->id, 'debit' => 10],
        ['account_id' => $this->revenue->id, 'credit' => 5],
        ['account_id' => $this->otherRevenue->id, 'credit' => 5],
    ], '1.005');
})->throws(MissingAccountMappingException::class);

test('transaction amounts follow the currency minor units', function () {
    $journal = foreignJournal($this->jpy, [
        ['account_id' => $this->receivable->id, 'debit' => '12345.6'],
        ['account_id' => $this->revenue->id, 'credit' => '12345.6'],
    ], '0.0067');

    expect($journal->lines()->where('account_id', $this->receivable->id)->value('currency_debit'))->toEqual('12346.0000');
});

test('the functional currency is always posted at a rate of one', function () {
    $journal = $this->journals->create([
        'journal_date' => now()->toDateString(),
        'exchange_rate' => '5',
        'lines' => [
            ['account_id' => $this->receivable->id, 'debit' => 100],
            ['account_id' => $this->revenue->id, 'credit' => 100],
        ],
    ]);

    expect($journal->exchange_rate)->toEqual('1.00000000')
        ->and($journal->fresh()->total_debit)->toEqual('100.0000');
});

test('an administrator maps accounts for automatic postings', function () {
    $user = companyUser(['finance.accounts.view', 'finance.accounts.update'], $this->company);
    $foreignAccount = app(CompanyContextService::class)->runAs(
        $otherId = Company::factory()->create()->id,
        fn () => Account::factory()->expense()->create(['company_id' => $otherId])
    );

    actingInCompany($user, $this->company)->get(route('finance.account-mappings.index'))->assertOk()->assertSee('Currency rounding differences');

    actingInCompany($user, $this->company)
        ->put(route('finance.account-mappings.update'), ['mappings' => ['fx_rounding' => $foreignAccount->id]])
        ->assertSessionHasErrors('mappings.fx_rounding');

    actingInCompany($user, $this->company)
        ->put(route('finance.account-mappings.update'), ['mappings' => ['fx_rounding' => $this->rounding->id]])
        ->assertSessionHasNoErrors();

    expect(AccountMapping::where('purpose', 'fx_rounding')->value('account_id'))->toBe($this->rounding->id);
});
