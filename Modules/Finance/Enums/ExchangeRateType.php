<?php

namespace Modules\Finance\Enums;

/**
 * IAS 21 rate types: spot for transactions, average for translating income and expenses,
 * closing for period-end revaluation and balance-sheet translation.
 */
enum ExchangeRateType: string
{
    case Spot = 'spot';
    case Average = 'average';
    case Closing = 'closing';
}
