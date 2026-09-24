<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Models\FiscalPeriod;
use Modules\Core\Models\FiscalYear;
use Modules\Core\Services\AccountingPeriodService;
use Modules\Core\Services\AuditService;
use Modules\Core\Services\DefaultAccountService;
use Modules\Core\Support\Money;
use Modules\Finance\Enums\AccountPurpose;
use Modules\Finance\Models\Journal;

/**
 * Year-end close: after the regular periods are closed, the year's revenue and expense balances are
 * transferred to retained earnings by a closing journal in the adjustment period, and the year is closed.
 * Balance-sheet accounts carry forward; reopening reverses the closing journal.
 */
class YearEndCloseService
{
    public function __construct(
        protected JournalService $journals,
        protected AccountingPeriodService $periods,
        protected DefaultAccountService $defaultAccounts,
        protected AuditService $audit,
    ) {}

    public function close(FiscalYear $fiscalYear): FiscalYear
    {
        return DB::transaction(function () use ($fiscalYear) {
            $fiscalYear = FiscalYear::whereKey($fiscalYear->id)->lockForUpdate()->firstOrFail();

            if (! $fiscalYear->isOpen()) {
                throw new InvalidAccountingTransactionException("Fiscal year {$fiscalYear->name} is not open.");
            }

            if ($fiscalYear->periods()->where('is_adjustment', false)->whereNotIn('status', ['CLOSED', 'LOCKED'])->exists()) {
                throw new InvalidAccountingTransactionException('Close every period of the year before closing the year.');
            }

            $hasUnposted = Journal::where('company_id', $fiscalYear->company_id)
                ->whereDate('journal_date', '>=', $fiscalYear->start_date->toDateString())
                ->whereDate('journal_date', '<=', $fiscalYear->end_date->toDateString())
                ->whereIn('status', [Journal::STATUS_DRAFT, Journal::STATUS_SUBMITTED, Journal::STATUS_APPROVED])
                ->exists();

            if ($hasUnposted) {
                throw new InvalidAccountingTransactionException('The year has unposted journals.');
            }

            $adjustmentPeriod = $this->periods->ensureAdjustmentPeriod($fiscalYear);

            if (! $adjustmentPeriod->canAcceptPosting()) {
                throw new InvalidAccountingTransactionException('The adjustment period of the year is not open.');
            }

            $closingJournal = $this->postClosingJournal($fiscalYear);

            $adjustmentPeriod->update(['status' => 'CLOSED', 'closed_at' => now(), 'closed_by' => auth()->id()]);
            $fiscalYear->update(['status' => 'CLOSED', 'closing_journal_id' => $closingJournal?->id, 'updated_by' => auth()->id()]);

            $this->audit->logCustom('Finance', 'FiscalYear', $fiscalYear->id, 'YEAR_END_CLOSE', ['closing_journal_id' => $closingJournal?->id], $fiscalYear->company_id);

            return $fiscalYear->fresh();
        });
    }

    public function reopen(FiscalYear $fiscalYear): FiscalYear
    {
        return DB::transaction(function () use ($fiscalYear) {
            $fiscalYear = FiscalYear::whereKey($fiscalYear->id)->lockForUpdate()->firstOrFail();

            if (! $fiscalYear->isClosed()) {
                throw new InvalidAccountingTransactionException("Fiscal year {$fiscalYear->name} is not closed.");
            }

            if (FiscalYear::where('company_id', $fiscalYear->company_id)->whereDate('start_date', '>', $fiscalYear->end_date->toDateString())->where('status', 'CLOSED')->exists()) {
                throw new InvalidAccountingTransactionException('Reopen later fiscal years first.');
            }

            FiscalPeriod::where('fiscal_year_id', $fiscalYear->id)->where('is_adjustment', true)
                ->update(['status' => 'OPEN', 'closed_at' => null, 'closed_by' => null]);

            if ($fiscalYear->closing_journal_id) {
                $this->journals->reverse(
                    Journal::findOrFail($fiscalYear->closing_journal_id),
                    "Reopening of fiscal year {$fiscalYear->name}",
                    $fiscalYear->end_date->toDateString(),
                    adjustmentPeriod: true,
                );
            }

            $fiscalYear->update(['status' => 'OPEN', 'closing_journal_id' => null, 'updated_by' => auth()->id()]);

            $this->audit->logCustom('Finance', 'FiscalYear', $fiscalYear->id, 'YEAR_END_REOPEN', [], $fiscalYear->company_id);

            return $fiscalYear->fresh();
        });
    }

    /**
     * Transfer each revenue and expense balance of the year to retained earnings; null when there is none.
     */
    protected function postClosingJournal(FiscalYear $fiscalYear): ?Journal
    {
        $currency = $fiscalYear->company->baseCurrency?->code ?? 'XXX';

        $balances = DB::table('journal_lines')
            ->join('journals', 'journals.id', '=', 'journal_lines.journal_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->where('journal_lines.company_id', $fiscalYear->company_id)
            ->whereIn('journals.status', Journal::LEDGER_STATUSES)
            ->whereDate('journals.journal_date', '>=', $fiscalYear->start_date->toDateString())
            ->whereDate('journals.journal_date', '<=', $fiscalYear->end_date->toDateString())
            ->whereIn('accounts.account_type', ['REVENUE', 'EXPENSE'])
            ->groupBy('journal_lines.account_id')
            ->selectRaw('journal_lines.account_id, SUM(journal_lines.debit - journal_lines.credit) AS balance')
            ->get()
            ->map(fn (object $row) => [$row->account_id, Money::of($row->balance, $currency)])
            ->reject(fn (array $row) => $row[1]->isZero());

        if ($balances->isEmpty()) {
            return null;
        }

        $lines = $balances->map(fn (array $row) => [
            'account_id' => $row[0],
            'description' => 'Year-end close',
            'debit' => $row[1]->isNegative() ? $row[1]->abs()->amount : '0',
            'credit' => $row[1]->isPositive() ? $row[1]->amount : '0',
        ])->values()->all();

        $net = $balances->reduce(fn (Money $total, array $row) => $total->plus($row[1]), Money::zero($currency));

        if (! $net->isZero()) {
            $lines[] = [
                'account_id' => $this->defaultAccounts->forPurpose($fiscalYear->company_id, AccountPurpose::RetainedEarnings),
                'description' => $net->isNegative() ? "Net profit {$fiscalYear->name}" : "Net loss {$fiscalYear->name}",
                'debit' => $net->isPositive() ? $net->amount : '0',
                'credit' => $net->isNegative() ? $net->abs()->amount : '0',
            ];
        }

        return $this->journals->postFromSource([
            'journal_date' => $fiscalYear->end_date->toDateString(),
            'adjustment_period' => true,
            'reference_type' => 'year_end_close',
            'reference_id' => $fiscalYear->id,
            'description' => "Year-end close {$fiscalYear->name}",
            'lines' => $lines,
        ]);
    }
}
