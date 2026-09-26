<?php

namespace Modules\Core\Support;

use Carbon\CarbonInterface;
use IntlDateFormatter;
use Modules\Core\Services\CompanyContextService;
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
        $formatter->setAttribute(NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFUP);

        return $formatter->formatCurrency((float) $money->amount, $money->currency);
    }

    /**
     * An amount without the currency symbol (for table columns), using the currency's minor units
     * and the locale's separators. Defaults to the active company's functional currency.
     */
    public static function amount(Money|string|int|float|null $amount, ?string $currency = null, ?string $locale = null): string
    {
        if ($amount instanceof Money) {
            $currency ??= $amount->currency;
            $amount = $amount->amount;
        }

        $currency ??= app(CompanyContextService::class)->getBaseCurrency()?->code;

        return self::number($amount ?? 0, $currency ? CurrencyPrecision::for($currency) : 2, $locale);
    }

    /**
     * A percentage rate such as 7.5 or 12.3456, showing only the decimals it needs.
     */
    public static function percent(string|int|float|null $rate, ?string $locale = null): string
    {
        return self::variableDecimals($rate ?? 0, 0, 4, $locale);
    }

    /**
     * An exchange rate, which needs more precision than an amount (e.g. JPY to USD is 0.0067).
     */
    public static function rate(string|int|float|null $rate, ?string $locale = null): string
    {
        return self::variableDecimals($rate ?? 0, 4, 8, $locale);
    }

    /**
     * A quantity with as many decimals as its unit of measure allows.
     */
    public static function quantity(string|int|float|null $quantity, int $decimals = 0, ?string $locale = null): string
    {
        return self::number($quantity ?? 0, $decimals, $locale);
    }

    /**
     * A unit price or cost: the currency's minor units, plus up to four decimals when the value has them.
     */
    public static function unitPrice(string|int|float|null $price, ?string $currency = null, ?string $locale = null): string
    {
        $currency ??= app(CompanyContextService::class)->getBaseCurrency()?->code;
        $minimum = $currency ? CurrencyPrecision::for($currency) : 2;

        return self::variableDecimals($price ?? 0, $minimum, max($minimum, 4), $locale);
    }

    public static function number(string|int|float $value, int $decimals = 2, ?string $locale = null): string
    {
        $formatter = new NumberFormatter($locale ?? app()->getLocale(), NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $decimals);
        $formatter->setAttribute(NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFUP);

        return $formatter->format((float) $value);
    }

    protected static function variableDecimals(string|int|float $value, int $minimumDecimals, int $maximumDecimals, ?string $locale): string
    {
        $formatter = new NumberFormatter($locale ?? app()->getLocale(), NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $minimumDecimals);
        $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $maximumDecimals);
        $formatter->setAttribute(NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFUP);

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
