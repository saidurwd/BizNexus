<?php

use Illuminate\Support\Carbon;
use Modules\Core\Models\Company;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Enums\CashFlowCategory;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\Journal;
use Modules\Finance\Models\JournalLine;
use Modules\Finance\Services\FinancialReportService;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $account = fn (string $type, ?CashFlowCategory $category, string $name) => Account::factory()->{$type}()
        ->create(['company_id' => $this->company->id, 'cash_flow_category' => $category, 'account_name' => $name]);

    $this->bank = $account('asset', CashFlowCategory::CashAndEquivalents, 'Main bank');
    $this->pettyCash = $account('asset', CashFlowCategory::CashAndEquivalents, 'Petty cash');
    $this->sales = $account('revenue', null, 'Sales');
    $this->equipment = $account('asset', CashFlowCategory::Investing, 'Equipment');
    $this->loan = $account('liability', CashFlowCategory::Financing, 'Bank loan');
    $this->fxGain = $account('revenue', null, 'Unrealised FX gain');
});

/**
 * @param  array<int, array{0: Account, 1: string, 2: string}>  $lines  account, debit, credit
 */
function postedJournal(string $date, array $lines, string $lineType = JournalLine::TYPE_STANDARD): void
{
    $journal = Journal::factory()->create(['company_id' => $lines[0][0]->company_id, 'status' => Journal::STATUS_POSTED, 'journal_date' => $date]);

    foreach ($lines as [$account, $debit, $credit]) {
        JournalLine::factory()->create(['journal_id' => $journal->id, 'account_id' => $account->id, 'debit' => $debit, 'credit' => $credit, 'line_type' => $lineType]);
    }
}

test('cash movements are classified by the account on the other side and reconcile to closing cash', function () {
    postedJournal('2026-05-20', [[$this->bank, '200', '0'], [$this->sales, '0', '200']]);
    postedJournal('2026-06-03', [[$this->bank, '1000', '0'], [$this->sales, '0', '1000']]);
    postedJournal('2026-06-10', [[$this->equipment, '300', '0'], [$this->bank, '0', '300']]);
    postedJournal('2026-06-12', [[$this->bank, '500', '0'], [$this->loan, '0', '500']]);
    postedJournal('2026-06-15', [[$this->pettyCash, '50', '0'], [$this->bank, '0', '50']]);
    postedJournal('2026-06-30', [[$this->bank, '10', '0'], [$this->fxGain, '0', '10']], JournalLine::TYPE_FX_REVALUATION);

    $report = app(CompanyContextService::class)->runAs($this->company->id, fn () => app(FinancialReportService::class)
        ->getCashFlow($this->company->id, Carbon::parse('2026-06-01'), Carbon::parse('2026-06-30')));

    expect($report)
        ->operating_total->toBe('1000.0000')
        ->investing_total->toBe('-300.0000')
        ->financing_total->toBe('500.0000')
        ->fx_effect->toBe('10.0000')
        ->opening_cash->toBe('200.0000')
        ->closing_cash->toBe('1410.0000')
        ->is_reconciled->toBeTrue()
        ->and(collect($report['operating_activities'])->pluck('description')->implode(' '))->not->toContain('Unrealised FX gain');
});

test('the statement page renders for the chosen period', function () {
    postedJournal('2026-06-03', [[$this->bank, '1000', '0'], [$this->sales, '0', '1000']]);

    actingInCompany(companyUser(['finance.reports.view'], $this->company), $this->company)
        ->get(route('finance.reports.cash-flow', ['start_date' => '2026-06-01', 'end_date' => '2026-06-30']))
        ->assertOk()
        ->assertSeeInOrder(['Cash flows from operating activities', 'Sales', '1,000.00', 'Cash and cash equivalents at the end of the period'])
        ->assertDontSee('does not equal closing cash');
});
