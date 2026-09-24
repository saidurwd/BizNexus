<?php

namespace Modules\Finance\Services;

use Carbon\CarbonInterface;
use Modules\Core\Support\Money;
use Modules\Finance\Contracts\TaxCalculator;
use Modules\Finance\Models\Tax;
use Modules\Finance\Support\TaxCalculation;
use Modules\Finance\Support\TaxComponentResult;

/**
 * Rate-table tax calculation: effective-dated rates, tax groups with ordered components, compound
 * components (taxed on the net plus earlier taxes) and price-inclusive taxes. Each component is rounded
 * to the currency's minor units; for inclusive prices the net absorbs the rounding so net + tax = gross.
 */
class InternalTaxCalculator implements TaxCalculator
{
    private const SCALE = 12;

    public function calculate(Tax $tax, Money $amount, CarbonInterface $date): TaxCalculation
    {
        $components = $this->components($tax, $date);

        if ($components === []) {
            return TaxCalculation::untaxed($amount);
        }

        $net = $tax->is_inclusive ? $amount->multipliedBy(bcdiv('1', $this->multiplier($components), self::SCALE)) : $amount;
        $results = [];
        $earlierTaxes = Money::zero($amount->currency);

        foreach ($components as [$component, $rate, $isCompound]) {
            $base = $isCompound ? $net->plus($earlierTaxes) : $net;
            $taxAmount = $base->multipliedBy(bcdiv($rate, '100', self::SCALE));
            $results[] = new TaxComponentResult($component, $rate, $base, $taxAmount);
            $earlierTaxes = $earlierTaxes->plus($taxAmount);
        }

        if ($tax->is_inclusive) {
            $net = $amount->minus($earlierTaxes);
        }

        return new TaxCalculation($net, $results);
    }

    /**
     * @return array<int, array{0: Tax, 1: string, 2: bool}> component, rate on the date, compound flag
     */
    protected function components(Tax $tax, CarbonInterface $date): array
    {
        if (! $tax->is_group) {
            return [[$tax, $tax->rateOn($date), false]];
        }

        return $tax->components->map(fn (Tax $component) => [$component, $component->rateOn($date), (bool) $component->pivot->is_compound])->all();
    }

    /**
     * Gross amount per one unit of net: 1 plus every component, compounding where flagged.
     *
     * @param  array<int, array{0: Tax, 1: string, 2: bool}>  $components
     */
    protected function multiplier(array $components): string
    {
        $taxes = '0';

        foreach ($components as [, $rate, $isCompound]) {
            $base = $isCompound ? bcadd('1', $taxes, self::SCALE) : '1';
            $taxes = bcadd($taxes, bcmul($base, bcdiv($rate, '100', self::SCALE), self::SCALE), self::SCALE);
        }

        return bcadd('1', $taxes, self::SCALE);
    }
}
