<?php

use Modules\Core\Enums\FiscalCalendarPattern;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Models\Company;
use Modules\Core\Models\FiscalPeriod;
use Modules\Core\Models\FiscalYear;
use Modules\Core\Services\AccountingPeriodService;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Enums\AccountPurpose;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\AccountMapping;
use Modules\Finance\Models\Journal;
use Modules\Finance\Models\JournalLine;
use Modules\Finance\Services\JournalService;
use Modules\Finance\Services\YearEndCloseService;

beforeEach(function () {
    $this->company = Company::factory()->create();
    app(CompanyContextService::class)->pinCompany($this->company->id);
    $this->year = FiscalYear::create(['company_id' => $this->company->id, 'name' => '2025', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31', 'status' => 'OPEN']);
    app(AccountingPeriodService::class)->createFiscalYearPeriods($this->year->id);

    $this->cash = Account::factory()->asset()->create(['company_id' => $this->company->id]);
    $this->revenue = Account::factory()->revenue()->create(['company_id' => $this->company->id]);
    $this->expense = Account::factory()->expense()->create(['company_id' => $this->company->id]);
    $this->retainedEarnings = Account::factory()->equity()->create(['company_id' => $this->company->id]);
    AccountMapping::create(['company_id' => $this->company->id, 'purpose' => AccountPurpose::RetainedEarnings, 'account_id' => $this->retainedEarnings->id]);
});

function postIn2025(int $debitAccountId, int $creditAccountId, string $amount, string $date = '2025-06-15'): Journal
{
    return app(JournalService::class)->postFromSource([
        'journal_date' => $date,
        'lines' => [['account_id' => $debitAccountId, 'debit' => $amount], ['account_id' => $creditAccountId, 'credit' => $amount]],
    ]);
}

function closeRegularPeriods(FiscalYear $year): void
{
    FiscalPeriod::where('fiscal_year_id', $year->id)->where('is_adjustment', false)->update(['status' => 'CLOSED']);
}

function ledgerBalance(int $accountId): string
{
    return (string) JournalLine::where('account_id', $accountId)->whereHas('journal', fn ($query) => $query->posted())
        ->get()->reduce(fn ($total, $line) => bcadd($total, bcsub($line->debit, $line->credit, 4), 4), '0');
}

test('closing the year transfers profit to retained earnings and closes the year', function () {
    postIn2025($this->cash->id, $this->revenue->id, '1000');
    postIn2025($this->expense->id, $this->cash->id, '300');
    closeRegularPeriods($this->year);

    $closed = app(YearEndCloseService::class)->close($this->year);

    expect($closed->status)->toBe('CLOSED')
        ->and(ledgerBalance($this->revenue->id))->toEqual('0.0000')
        ->and(ledgerBalance($this->expense->id))->toEqual('0.0000')
        ->and(ledgerBalance($this->retainedEarnings->id))->toEqual('-700.0000')
        ->and(ledgerBalance($this->cash->id))->toEqual('700.0000')
        ->and(Journal::find($closed->closing_journal_id)->fiscalPeriod->is_adjustment)->toBeTrue()
        ->and(FiscalPeriod::where('fiscal_year_id', $this->year->id)->where('is_adjustment', true)->value('status'))->toBe('CLOSED');
});

test('the year cannot be closed while a regular period is open', function () {
    app(YearEndCloseService::class)->close($this->year);
})->throws(InvalidAccountingTransactionException::class, 'Close every period of the year');

test('the year cannot be closed with unposted journals', function () {
    Journal::factory()->submitted()->create(['company_id' => $this->company->id, 'journal_date' => '2025-11-30']);
    closeRegularPeriods($this->year);

    app(YearEndCloseService::class)->close($this->year);
})->throws(InvalidAccountingTransactionException::class, 'unposted journals');

test('reopening reverses the closing entry', function () {
    postIn2025($this->cash->id, $this->revenue->id, '1000');
    closeRegularPeriods($this->year);
    $service = app(YearEndCloseService::class);

    $reopened = $service->reopen($service->close($this->year));

    expect($reopened->status)->toBe('OPEN')
        ->and($reopened->closing_journal_id)->toBeNull()
        ->and(ledgerBalance($this->revenue->id))->toEqual('-1000.0000')
        ->and(ledgerBalance($this->retainedEarnings->id))->toEqual('0.0000');
});

test('ordinary postings on the last day use the regular period, not the adjustment period', function () {
    $journal = postIn2025($this->cash->id, $this->revenue->id, '50', '2025-12-31');

    expect($journal->fiscalPeriod->is_adjustment)->toBeFalse()
        ->and($journal->fiscalPeriod->period_number)->toBe(12);
});

test('closing the last period no longer closes the year by itself', function () {
    $periods = app(AccountingPeriodService::class);
    $userId = companyUser([], $this->company)->id;
    FiscalPeriod::where('fiscal_year_id', $this->year->id)->where('is_adjustment', false)->get()
        ->each(fn (FiscalPeriod $period) => $periods->closePeriod($period->id, $userId));

    expect($this->year->fresh()->status)->toBe('OPEN');
});

test('week-based calendars create the pattern periods ending on the year end', function (FiscalCalendarPattern $pattern, int $regularPeriods, int $firstPeriodDays) {
    $year = FiscalYear::create([
        'company_id' => $this->company->id, 'name' => '2026 retail', 'start_date' => '2026-01-04', 'end_date' => '2027-01-02',
        'status' => 'OPEN', 'period_pattern' => $pattern,
    ]);

    app(AccountingPeriodService::class)->createFiscalYearPeriods($year->id);

    $regular = $year->periods()->where('is_adjustment', false)->orderBy('period_number')->get();
    expect($regular)->toHaveCount($regularPeriods)
        ->and($regular->first()->start_date->diffInDays($regular->first()->end_date) + 1)->toEqual($firstPeriodDays)
        ->and($regular->last()->end_date->toDateString())->toBe('2027-01-02')
        ->and($year->periods()->where('is_adjustment', true)->count())->toBe(1);
})->with([
    '4-4-5' => [FiscalCalendarPattern::FourFourFive, 12, 28],
    '5-4-4' => [FiscalCalendarPattern::FiveFourFour, 12, 35],
    '13 periods' => [FiscalCalendarPattern::ThirteenPeriods, 13, 28],
]);

test('fiscal years may not overlap', function () {
    $user = companyUser(['core.fiscal-years.create'], $this->company);

    actingInCompany($user, $this->company)
        ->post(route('core.fiscal-years.store'), ['name' => 'Overlap', 'start_date' => '2025-07-01', 'end_date' => '2026-06-30', 'period_pattern' => 'monthly'])
        ->assertSessionHasErrors(['start_date' => 'The fiscal year overlaps an existing fiscal year.']);
});

test('an authorised user closes the year from the periods page', function () {
    $user = companyUser(['core.fiscal-years.close', 'core.periods.view'], $this->company);
    closeRegularPeriods($this->year);

    actingInCompany($user, $this->company)->get(route('core.periods.index'))->assertOk()->assertSee('Close year');
    actingInCompany($user, $this->company)->post(route('core.fiscal-years.close', $this->year->id))->assertRedirect(route('core.periods.index'));

    expect($this->year->fresh()->status)->toBe('CLOSED');
});
