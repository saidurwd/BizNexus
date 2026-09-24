<?php

namespace Modules\Finance\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Models\Company;
use Modules\Core\Models\Currency;
use Modules\Core\Services\DefaultAccountService;
use Modules\Core\Support\Money;
use Modules\Finance\Enums\AccountPurpose;
use Modules\Finance\Enums\ExchangeRateType;
use Modules\Finance\Models\FxRevaluation;
use Modules\Finance\Models\Journal;
use Modules\Finance\Models\JournalLine;

/**
 * Period-end revaluation of foreign-currency monetary items (IAS 21.23): each account flagged for
 * revaluation is restated per currency at the closing rate. The unrealised difference is posted on the
 * revaluation date and reversed automatically on the next day (reverse-and-revalue method).
 */
class FxRevaluationService
{
    public function __construct(
        protected JournalService $journals,
        protected ExchangeRateService $exchangeRates,
        protected DefaultAccountService $defaultAccounts,
    ) {}

    public function revalue(Company $company, CarbonInterface $date): FxRevaluation
    {
        if (FxRevaluation::where('company_id', $company->id)->whereDate('revaluation_date', $date->toDateString())->exists()) {
            throw new InvalidAccountingTransactionException("Foreign currency balances were already revalued on {$date->toDateString()}.");
        }

        return DB::transaction(function () use ($company, $date) {
            $functionalCurrency = $company->baseCurrency->code;
            $lines = [];
            $netGain = Money::zero($functionalCurrency);

            foreach ($this->foreignBalances($company, $date) as $balance) {
                $currency = Currency::findOrFail($balance->currency_id);
                $closingRate = $this->exchangeRates->rate($company, $currency, $date, ExchangeRateType::Closing);
                $restated = Money::of($balance->currency_balance, $currency->code)->convertedTo($functionalCurrency, $closingRate);
                $difference = $restated->minus(Money::of($balance->functional_balance, $functionalCurrency));

                if ($difference->isZero()) {
                    continue;
                }

                $lines[] = [
                    'account_id' => $balance->account_id,
                    'description' => "Revaluation of {$currency->code} balance at {$closingRate}",
                    'line_type' => JournalLine::TYPE_FX_REVALUATION,
                    'functional_amount' => $difference->amount,
                ];
                $netGain = $netGain->plus($difference);
            }

            $revaluation = FxRevaluation::create([
                'company_id' => $company->id,
                'revaluation_date' => $date->toDateString(),
                'net_gain_loss' => $netGain->amount,
                'created_by' => Auth::id(),
            ]);

            if ($lines === []) {
                return $revaluation;
            }

            $gains = collect($lines)->filter(fn (array $line) => bccomp($line['functional_amount'], '0', 4) === 1)
                ->reduce(fn (Money $total, array $line) => $total->plus(Money::of($line['functional_amount'], $functionalCurrency)), Money::zero($functionalCurrency));
            $losses = $gains->minus($netGain);

            foreach ([[AccountPurpose::UnrealizedFxGain, $gains->negated()], [AccountPurpose::UnrealizedFxLoss, $losses]] as [$purpose, $amount]) {
                if (! $amount->isZero()) {
                    $lines[] = [
                        'account_id' => $this->defaultAccounts->forPurpose($company->id, $purpose),
                        'description' => $purpose->label(),
                        'line_type' => JournalLine::TYPE_FX_REVALUATION,
                        'functional_amount' => $amount->amount,
                    ];
                }
            }

            $journal = $this->journals->postFromSource([
                'journal_date' => $date->toDateString(),
                'reference_type' => 'fx_revaluation',
                'reference_id' => $revaluation->id,
                'description' => "Foreign currency revaluation {$date->toDateString()}",
                'lines' => $lines,
            ]);

            $reversal = $this->journals->reverse($journal, 'Automatic reversal of foreign currency revaluation', $date->copy()->addDay()->toDateString());

            $revaluation->update(['journal_id' => $journal->id, 'reversal_journal_id' => $reversal->id]);

            return $revaluation->fresh();
        });
    }

    /**
     * Posted balances per revaluable account and foreign currency, in both currencies, up to the date.
     *
     * @return iterable<int, object{account_id: int, currency_id: int, currency_balance: string, functional_balance: string}>
     */
    protected function foreignBalances(Company $company, CarbonInterface $date): iterable
    {
        return DB::table('journal_lines')
            ->join('journals', 'journals.id', '=', 'journal_lines.journal_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->where('journal_lines.company_id', $company->id)
            ->where('accounts.revalue_foreign_currency', true)
            ->whereIn('journals.status', Journal::LEDGER_STATUSES)
            ->whereDate('journals.journal_date', '<=', $date->toDateString())
            ->whereNotNull('journals.currency_id')
            ->where('journals.currency_id', '!=', $company->base_currency_id)
            ->groupBy('journal_lines.account_id', 'journals.currency_id')
            ->selectRaw('journal_lines.account_id, journals.currency_id, SUM(journal_lines.currency_debit - journal_lines.currency_credit) AS currency_balance, SUM(journal_lines.debit - journal_lines.credit) AS functional_balance')
            ->get();
    }
}
