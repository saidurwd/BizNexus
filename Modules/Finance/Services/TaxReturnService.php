<?php

namespace Modules\Finance\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Modules\Core\Models\Company;
use Modules\Core\Support\Money;
use Modules\Finance\Models\TaxTransaction;

/**
 * Tax return basis for a period, in the company's functional currency: output tax on sales, reverse-charge
 * tax self-assessed on purchases, recoverable and non-recoverable input tax, and tax withheld at source.
 */
class TaxReturnService
{
    public const BOX_OUTPUT = 'output';

    public const BOX_REVERSE_CHARGE_OUTPUT = 'reverse_charge_output';

    public const BOX_INPUT = 'input';

    public const BOX_NON_RECOVERABLE = 'non_recoverable';

    public const BOX_WITHHOLDING = 'withholding';

    /**
     * @return array{boxes: array<string, Collection<int, array{tax_code: string, tax_name: string, taxable: Money, tax: Money}>>, totals: array<string, Money>, net_payable: Money, currency: string}
     */
    public function summarise(Company $company, CarbonInterface $from, CarbonInterface $to): array
    {
        $currency = $company->baseCurrency?->code ?? 'XXX';

        $transactions = TaxTransaction::with('tax')
            ->where('company_id', $company->id)
            ->whereDate('tax_date', '>=', $from->toDateString())
            ->whereDate('tax_date', '<=', $to->toDateString())
            ->get();

        $boxes = $transactions
            ->groupBy(fn (TaxTransaction $transaction) => $this->box($transaction))
            ->map(fn (Collection $inBox) => $inBox->groupBy('tax_id')->map(fn (Collection $forTax) => [
                'tax_code' => $forTax->first()->tax->tax_code,
                'tax_name' => $forTax->first()->tax->tax_name,
                'taxable' => $this->sum($forTax, 'taxable_amount', $currency),
                'tax' => $this->sum($forTax, 'tax_amount', $currency),
            ])->sortBy('tax_code')->values());

        $totals = collect([self::BOX_OUTPUT, self::BOX_REVERSE_CHARGE_OUTPUT, self::BOX_INPUT, self::BOX_NON_RECOVERABLE, self::BOX_WITHHOLDING])
            ->mapWithKeys(fn (string $box) => [$box => ($boxes[$box] ?? collect())->reduce(fn (Money $total, array $row) => $total->plus($row['tax']), Money::zero($currency))])
            ->all();

        return [
            'boxes' => $boxes->all(),
            'totals' => $totals,
            'net_payable' => $totals[self::BOX_OUTPUT]->plus($totals[self::BOX_REVERSE_CHARGE_OUTPUT])->minus($totals[self::BOX_INPUT]),
            'currency' => $currency,
        ];
    }

    protected function box(TaxTransaction $transaction): string
    {
        return match (true) {
            $transaction->transaction_type === 'WITHHOLDING' => self::BOX_WITHHOLDING,
            $transaction->transaction_type === 'OUTPUT' && $transaction->is_reverse_charge && $transaction->invoice_id !== null => self::BOX_REVERSE_CHARGE_OUTPUT,
            $transaction->transaction_type === 'OUTPUT' => self::BOX_OUTPUT,
            $transaction->is_recoverable => self::BOX_INPUT,
            default => self::BOX_NON_RECOVERABLE,
        };
    }

    /**
     * Sum a column converted to the functional currency at each transaction's exchange rate.
     *
     * @param  Collection<int, TaxTransaction>  $transactions
     */
    protected function sum(Collection $transactions, string $column, string $currency): Money
    {
        return $transactions->reduce(
            fn (Money $total, TaxTransaction $transaction) => $total->plus(
                Money::of($transaction->{$column}, $transaction->currency_code ?: $currency)->convertedTo($currency, (string) ($transaction->exchange_rate ?: 1))
            ),
            Money::zero($currency)
        );
    }
}
