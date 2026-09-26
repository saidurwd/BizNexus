<?php

namespace Modules\Core\Support;

use Carbon\CarbonInterface;
use IntlDateFormatter;
use NumberFormatter;

/**
 * Locale-aware display of money, numbers and dates (ext-intl), e.g. 1,234.50 in en, 1.234,50 in de,
 * ١٬٢٣٤٫٥٠ in ar, with the currency's own minor units.
 */
class Formatter
{
    public static function money(Money|string|int|float $amount, ?string $currency = null, ?string $locale = null): string
    {
        $money = $amount instanceof Money ? $amount : Money::of($amount, $currency ?? 'XXX');
        $formatter = new NumberFormatter($locale ?? app()->getLocale(), NumberFormatter::CURRENCY);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, CurrencyPrecision::for($money->currency));

        return $formatter->formatCurrency((float) $money->amount, $money->currency);
    }

    public static function number(string|int|float $value, int $decimals = 2, ?string $locale = null): string
    {
        $formatter = new NumberFormatter($locale ?? app()->getLocale(), NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimals);

        return $formatter->format((float) $value);
    }

    public static function date(?CarbonInterface $date, ?string $locale = null): string
    {
        if (! $date) {
            return '';
        }

        return (new IntlDateFormatter($locale ?? app()->getLocale(), IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE, $date->getTimezone()->getName()))
            ->format($date->toDateTimeImmutable());
    }
}
