<?php

use Modules\Finance\Services\InternalTaxCalculator;

return [

    /*
    |--------------------------------------------------------------------------
    | Tax Calculator
    |--------------------------------------------------------------------------
    |
    | Implementation of Modules\Finance\Contracts\TaxCalculator. Replace with an
    | adapter for an external tax engine to delegate determination and rates.
    |
    */

    'calculator' => InternalTaxCalculator::class,

];
