<?php

namespace Modules\Finance\Support;

use Modules\Core\Support\Money;

/**
 * The result of taxing one line: net and gross amounts and each tax component.
 */
final class TaxCalculation
{
    /**
     * @param  array<int, TaxComponentResult>  $components
     */
    public function __construct(
        public readonly Money $net,
        public readonly array $components,
    ) {}

    public static function untaxed(Money $net): self
    {
        return new self($net, []);
    }

    public function totalTax(): Money
    {
        return $this->sum($this->components);
    }

    public function recoverableTax(): Money
    {
        return $this->sum(array_filter($this->components, fn (TaxComponentResult $component) => $component->isRecoverable()));
    }

    public function nonRecoverableTax(): Money
    {
        return $this->sum(array_filter($this->components, fn (TaxComponentResult $component) => ! $component->isRecoverable()));
    }

    public function gross(): Money
    {
        return $this->net->plus($this->totalTax());
    }

    /**
     * @param  array<int, TaxComponentResult>  $components
     */
    private function sum(array $components): Money
    {
        return array_reduce($components, fn (Money $total, TaxComponentResult $component) => $total->plus($component->amount), Money::zero($this->net->currency));
    }
}
