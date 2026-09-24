<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Collection;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Support\Money;
use Modules\Finance\Contracts\TaxCalculator;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Finance\Models\TaxRule;
use Modules\Finance\Models\TaxTransaction;
use Modules\Finance\Support\TaxCalculation;

/**
 * Tax on sales and purchase invoices. Header discounts are spread over the lines in proportion to their
 * amounts before tax; lines without a tax code get one from the determination rules; reverse-charge lines
 * carry no charged tax (the buyer self-assesses on purchases). Posting uses the same per-line results.
 */
class DocumentTaxService
{
    public function __construct(
        protected TaxCalculator $calculator,
        protected TaxDeterminationService $determination,
    ) {}

    /**
     * Determine tax codes, recalculate every line and the invoice totals, and save them.
     */
    public function recalculate(SupplierInvoice|CustomerInvoice $invoice): void
    {
        $this->determineMissingTaxCodes($invoice);

        $calculations = $this->calculations($invoice);
        $currency = $this->currencyCode($invoice);
        $subtotal = Money::zero($currency);
        $chargedTax = Money::zero($currency);

        foreach ($invoice->lines as $line) {
            $calculation = $calculations[$line->id];
            $lineTax = $line->is_reverse_charge ? Money::zero($currency) : $calculation->totalTax();

            $line->forceFill([
                'subtotal' => $calculation->net->amount,
                'tax_amount' => $lineTax->amount,
                'total_amount' => $calculation->net->plus($lineTax)->amount,
            ])->save();

            $subtotal = $subtotal->plus($calculation->net);
            $chargedTax = $chargedTax->plus($lineTax);
        }

        $invoice->forceFill([
            'subtotal' => $subtotal->amount,
            'tax_amount' => $chargedTax->amount,
            'total_amount' => $subtotal->plus($chargedTax)->amount,
            'outstanding_amount' => $subtotal->plus($chargedTax)->amount,
        ])->save();
    }

    /**
     * Tax result per line id, with the header discount allocated to the lines.
     *
     * @return array<int, TaxCalculation>
     */
    public function calculations(SupplierInvoice|CustomerInvoice $invoice): array
    {
        $currency = $this->currencyCode($invoice);
        $lines = $invoice->lines()->with('tax.components')->get();
        $invoice->setRelation('lines', $lines);

        $amounts = $lines->mapWithKeys(fn ($line) => [$line->id => Money::of((string) $line->quantity, $currency)
            ->multipliedBy((string) $line->unit_price)
            ->minus(Money::of($line->discount_amount ?? 0, $currency))]);

        $discounts = $this->allocateHeaderDiscount($amounts, Money::of($invoice->discount_amount ?? 0, $currency));

        return $lines->mapWithKeys(function ($line) use ($amounts, $discounts, $invoice) {
            $base = $amounts[$line->id]->minus($discounts[$line->id]);

            return [$line->id => $line->tax
                ? $this->calculator->calculate($line->tax, $base, $invoice->invoice_date)
                : TaxCalculation::untaxed($base)];
        })->all();
    }

    /**
     * Record the tax of a posted invoice for tax returns: purchases as input tax (reverse charge also as
     * self-assessed output tax), sales as output tax (reverse charge with no tax charged).
     */
    public function recordTransactions(SupplierInvoice|CustomerInvoice $invoice, ?int $journalId = null): void
    {
        $isPurchase = $invoice instanceof SupplierInvoice;

        foreach ($this->calculations($invoice) as $lineId => $calculation) {
            $line = $invoice->lines->firstWhere('id', $lineId);

            foreach ($calculation->components as $component) {
                $types = match (true) {
                    $isPurchase && $line->is_reverse_charge => ['INPUT', 'OUTPUT'],
                    $isPurchase => ['INPUT'],
                    default => ['OUTPUT'],
                };

                foreach ($types as $type) {
                    TaxTransaction::create([
                        'company_id' => $invoice->company_id,
                        'tax_id' => $component->tax->id,
                        'transaction_type' => $type,
                        'is_reverse_charge' => (bool) $line->is_reverse_charge,
                        'is_recoverable' => $component->isRecoverable(),
                        'invoice_id' => $isPurchase ? $invoice->id : null,
                        'customer_invoice_id' => $isPurchase ? null : $invoice->id,
                        'taxable_amount' => $component->taxableBase->amount,
                        'tax_amount' => ! $isPurchase && $line->is_reverse_charge ? '0' : $component->amount->amount,
                        'exchange_rate' => $invoice->exchange_rate ?? 1,
                        'currency_code' => $calculation->net->currency,
                        'tax_date' => $invoice->invoice_date,
                        'reference_number' => $invoice->invoice_number,
                        'notes' => $journalId ? "Journal #{$journalId}" : null,
                    ]);
                }
            }
        }
    }

    public function requireAccount(?int $accountId, string $taxCode, string $side): int
    {
        return $accountId ?? throw new InvalidAccountingTransactionException("Tax {$taxCode} has no {$side} tax account.");
    }

    protected function determineMissingTaxCodes(SupplierInvoice|CustomerInvoice $invoice): void
    {
        $isPurchase = $invoice instanceof SupplierInvoice;
        $party = $isPurchase ? $invoice->supplier : $invoice->customer;

        foreach ($invoice->lines()->whereNull('tax_id')->get() as $line) {
            $rule = $this->determination->determine(
                $invoice->company,
                $isPurchase ? TaxRule::DIRECTION_PURCHASE : TaxRule::DIRECTION_SALES,
                $party?->country_code,
                filled($party?->tax_number),
                $line->supply_type,
            );

            if ($rule) {
                $line->forceFill(['tax_id' => $rule->tax_id, 'is_reverse_charge' => $rule->reverse_charge])->save();
            }
        }

        $invoice->unsetRelation('lines');
    }

    /**
     * Spread the header discount over lines in proportion to their amounts; the last line takes the remainder.
     *
     * @param  Collection<int, Money>  $amounts
     * @return array<int, Money>
     */
    protected function allocateHeaderDiscount(Collection $amounts, Money $discount): array
    {
        $total = $amounts->reduce(fn (Money $sum, Money $amount) => $sum->plus($amount), Money::zero($discount->currency));
        $allocated = [];
        $remaining = $discount;
        $lastId = $amounts->keys()->last();

        foreach ($amounts as $id => $amount) {
            if ($discount->isZero() || $total->isZero()) {
                $allocated[$id] = Money::zero($discount->currency);

                continue;
            }

            $share = $id === $lastId ? $remaining : $discount->multipliedBy(bcdiv($amount->amount, $total->amount, 12));
            $allocated[$id] = $share;
            $remaining = $remaining->minus($share);
        }

        return $allocated;
    }

    protected function currencyCode(SupplierInvoice|CustomerInvoice $invoice): string
    {
        return $invoice->currency?->code ?? $invoice->company->baseCurrency?->code ?? 'XXX';
    }
}
