<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Collection;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Models\Company;
use Modules\Core\Models\Currency;
use Modules\Core\Services\DefaultAccountService;
use Modules\Core\Support\Money;
use Modules\Finance\Enums\AccountPurpose;
use Modules\Finance\Models\JournalLine;

/**
 * Realised exchange differences (IAS 21.28): when a foreign-currency invoice is settled at a rate different
 * from the one it was booked at, the functional-currency difference is recognised as a gain or loss and the
 * control account (payables/receivables) is cleared at the invoice rate.
 */
class RealizedExchangeDifferenceService
{
    public const SIDE_PAYABLE = 'payable';

    public const SIDE_RECEIVABLE = 'receivable';

    public function __construct(protected DefaultAccountService $defaultAccounts) {}

    /**
     * Journal lines for the settlement of the allocated invoices (none when there is no difference).
     *
     * @param  Collection<int, object{amount: mixed, invoice: object{currency_id: ?int, exchange_rate: mixed}}>  $allocations
     * @return array<int, array<string, mixed>>
     */
    public function settlementLines(Company $company, Collection $allocations, ?int $settlementCurrencyId, string $settlementRate, int $controlAccountId, string $side): array
    {
        $functionalCurrency = $company->baseCurrency?->code ?? 'XXX';
        $settlementCurrencyId ??= $company->base_currency_id;
        $settlementCurrency = Currency::find($settlementCurrencyId)?->code ?? $functionalCurrency;
        $difference = Money::zero($functionalCurrency);

        foreach ($allocations as $allocation) {
            $invoiceCurrencyId = $allocation->invoice->currency_id ?? $company->base_currency_id;

            if ((int) $invoiceCurrencyId !== (int) $settlementCurrencyId) {
                throw new InvalidAccountingTransactionException('An invoice can only be settled in its own currency.');
            }

            $amount = Money::of($allocation->amount, $settlementCurrency);
            $difference = $difference->plus(
                $amount->convertedTo($functionalCurrency, $settlementRate)
                    ->minus($amount->convertedTo($functionalCurrency, (string) ($allocation->invoice->exchange_rate ?? '1')))
            );
        }

        if ($difference->isZero()) {
            return [];
        }

        // Payables are relieved at the settlement rate; the excess debit (positive difference) is a loss.
        // Receivables are relieved at the settlement rate; the excess credit (positive difference) is a gain.
        $controlAdjustment = $side === self::SIDE_PAYABLE ? $difference->negated() : $difference;
        $isGain = $side === self::SIDE_PAYABLE ? $difference->isNegative() : $difference->isPositive();

        return [
            [
                'account_id' => $controlAccountId,
                'description' => 'Realised exchange difference on settlement',
                'line_type' => JournalLine::TYPE_FX_REALIZED,
                'functional_amount' => $controlAdjustment->amount,
            ],
            [
                'account_id' => $this->defaultAccounts->forPurpose($company->id, $isGain ? AccountPurpose::RealizedFxGain : AccountPurpose::RealizedFxLoss),
                'description' => $isGain ? 'Realised exchange gain' : 'Realised exchange loss',
                'line_type' => JournalLine::TYPE_FX_REALIZED,
                'functional_amount' => $controlAdjustment->negated()->amount,
            ],
        ];
    }
}
