<?php

namespace Modules\Finance\Enums;

/**
 * IAS 7 classification of an account's movements in the statement of cash flows.
 */
enum CashFlowCategory: string
{
    case CashAndEquivalents = 'cash';
    case Operating = 'operating';
    case Investing = 'investing';
    case Financing = 'financing';

    public function label(): string
    {
        return match ($this) {
            self::CashAndEquivalents => 'Cash and cash equivalents',
            self::Operating => 'Operating activities',
            self::Investing => 'Investing activities',
            self::Financing => 'Financing activities',
        };
    }
}
