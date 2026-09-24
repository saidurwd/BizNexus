<?php

namespace Modules\Finance\Contracts;

use Carbon\CarbonInterface;
use Modules\Core\Support\Money;
use Modules\Finance\Models\Tax;
use Modules\Finance\Support\TaxCalculation;

/**
 * Calculates tax for one document line. Bound in FinanceServiceProvider from config('finance.tax.calculator'),
 * so an external engine (Avalara, Vertex, a national e-invoicing API) can replace the internal one.
 */
interface TaxCalculator
{
    /**
     * @param  Money  $amount  the line amount: gross when the tax is price-inclusive, otherwise net
     */
    public function calculate(Tax $tax, Money $amount, CarbonInterface $date): TaxCalculation;
}
