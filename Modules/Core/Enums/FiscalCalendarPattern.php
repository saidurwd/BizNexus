<?php

namespace Modules\Core\Enums;

/**
 * How a fiscal year is divided into periods. Week-based patterns suit 52/53-week years; the last
 * period always ends on the fiscal year end, absorbing a 53rd week.
 */
enum FiscalCalendarPattern: string
{
    case Monthly = 'monthly';
    case FourFourFive = '4-4-5';
    case FourFiveFour = '4-5-4';
    case FiveFourFour = '5-4-4';
    case ThirteenPeriods = '13x4';

    /**
     * Weeks per period, or null for calendar months.
     *
     * @return array<int, int>|null
     */
    public function weeksPerPeriod(): ?array
    {
        return match ($this) {
            self::Monthly => null,
            self::FourFourFive => array_merge(...array_fill(0, 4, [4, 4, 5])),
            self::FourFiveFour => array_merge(...array_fill(0, 4, [4, 5, 4])),
            self::FiveFourFour => array_merge(...array_fill(0, 4, [5, 4, 4])),
            self::ThirteenPeriods => array_fill(0, 13, 4),
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Calendar months',
            self::FourFourFive => '4-4-5 weeks per quarter',
            self::FourFiveFour => '4-5-4 weeks per quarter',
            self::FiveFourFour => '5-4-4 weeks per quarter',
            self::ThirteenPeriods => '13 periods of 4 weeks',
        };
    }
}
