<?php

namespace Modules\Finance\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\Company;
use Modules\Core\Support\Money;
use Modules\Finance\Enums\ExchangeRateType;
use Modules\Finance\Models\ConsolidationGroup;
use Modules\Finance\Models\Journal;

/**
 * Consolidated trial balance of a group in the parent's functional currency (IFRS 10, IAS 21):
 * member balances are translated at the parent's closing rate (assets, liabilities, equity) or average rate
 * (income, expenses); balances with other members (by trading partner) are eliminated; the remaining
 * difference is the translation reserve; non-controlling interests are measured on members' net assets.
 */
class ConsolidationService
{
    private const BALANCE_SHEET_TYPES = ['ASSET', 'LIABILITY', 'EQUITY'];

    public function __construct(protected ExchangeRateService $exchangeRates) {}

    /**
     * @return array{currency: string, members: array<int, array<string, mixed>>, rows: Collection<int, array<string, mixed>>, translation_difference: Money, net_profit: Money, nci_net_assets: Money, nci_profit: Money}
     */
    public function trialBalance(ConsolidationGroup $group, CarbonInterface $asOf): array
    {
        $parent = $group->parentCompany;
        $currency = $parent->baseCurrency->code;
        $members = $group->members()->with('baseCurrency')->get();
        $memberIds = $members->pluck('id')->all();
        $zero = Money::zero($currency);

        $rows = collect();
        $memberSummaries = [];
        $nciNetAssets = $zero;
        $nciProfit = $zero;

        foreach ($members as $member) {
            [$closingRate, $averageRate] = $this->translationRates($parent, $member, $asOf);
            $minorityShare = bcdiv(bcsub('100', (string) $member->pivot->ownership_percent, 6), '100', 6);
            $netAssets = $zero;
            $profit = $zero;

            foreach ($this->balances($member, $memberIds, $asOf) as $balance) {
                $rate = in_array($balance->account_type, self::BALANCE_SHEET_TYPES, true) ? $closingRate : $averageRate;
                $translated = Money::of($balance->balance, $member->baseCurrency->code)->convertedTo($currency, $rate);
                $intercompany = Money::of($balance->intercompany_balance, $member->baseCurrency->code)->convertedTo($currency, $rate);

                $row = $rows->get($balance->account_code, [
                    'account_code' => $balance->account_code,
                    'account_name' => $balance->account_name,
                    'account_type' => $balance->account_type,
                    'by_company' => [],
                    'eliminations' => $zero,
                    'consolidated' => $zero,
                ]);
                $row['by_company'][$member->id] = ($row['by_company'][$member->id] ?? $zero)->plus($translated);
                $row['eliminations'] = $row['eliminations']->minus($intercompany);
                $row['consolidated'] = $row['consolidated']->plus($translated)->minus($intercompany);
                $rows->put($balance->account_code, $row);

                if (in_array($balance->account_type, ['ASSET', 'LIABILITY'], true)) {
                    $netAssets = $netAssets->plus($translated);
                } elseif (in_array($balance->account_type, ['REVENUE', 'EXPENSE'], true)) {
                    $profit = $profit->minus($translated);
                }
            }

            $nciNetAssets = $nciNetAssets->plus($netAssets->multipliedBy($minorityShare));
            $nciProfit = $nciProfit->plus($profit->multipliedBy($minorityShare));
            $memberSummaries[] = [
                'company' => $member,
                'ownership_percent' => (string) $member->pivot->ownership_percent,
                'closing_rate' => $closingRate,
                'average_rate' => $averageRate,
            ];
        }

        $sorted = $rows->sortKeys()->values();
        $imbalance = $sorted->reduce(fn (Money $total, array $row) => $total->plus($row['consolidated']), $zero);
        $netProfit = $sorted->whereIn('account_type', ['REVENUE', 'EXPENSE'])->reduce(fn (Money $total, array $row) => $total->minus($row['consolidated']), $zero);

        return [
            'currency' => $currency,
            'members' => $memberSummaries,
            'rows' => $sorted,
            'translation_difference' => $imbalance->negated(),
            'net_profit' => $netProfit,
            'nci_net_assets' => $nciNetAssets,
            'nci_profit' => $nciProfit,
        ];
    }

    /**
     * @return array{0: string, 1: string} closing and average rate from the member's to the parent's currency
     */
    protected function translationRates(Company $parent, Company $member, CarbonInterface $asOf): array
    {
        if ((int) $member->base_currency_id === (int) $parent->base_currency_id) {
            return ['1', '1'];
        }

        return [
            $this->exchangeRates->rate($parent, $member->baseCurrency, $asOf, ExchangeRateType::Closing),
            $this->exchangeRates->rate($parent, $member->baseCurrency, $asOf, ExchangeRateType::Average),
        ];
    }

    /**
     * Functional balances per account of a member up to the date, with the part held with other members.
     *
     * @param  array<int, int>  $memberIds
     */
    protected function balances(Company $member, array $memberIds, CarbonInterface $asOf): Collection
    {
        return DB::table('journal_lines')
            ->join('journals', 'journals.id', '=', 'journal_lines.journal_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->where('journal_lines.company_id', $member->id)
            ->whereIn('journals.status', Journal::LEDGER_STATUSES)
            ->whereDate('journals.journal_date', '<=', $asOf->toDateString())
            ->groupBy('accounts.account_code', 'accounts.account_name', 'accounts.account_type')
            ->selectRaw('accounts.account_code, accounts.account_name, accounts.account_type, SUM(journal_lines.debit - journal_lines.credit) AS balance')
            ->selectRaw('SUM(CASE WHEN journal_lines.counterparty_company_id IN ('.implode(',', array_map('intval', $memberIds)).') THEN journal_lines.debit - journal_lines.credit ELSE 0 END) AS intercompany_balance')
            ->get();
    }
}
