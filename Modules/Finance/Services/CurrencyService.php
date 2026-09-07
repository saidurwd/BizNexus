<?php

namespace Modules\Finance\Services;

use Modules\Core\Models\Currency;
use InvalidArgumentException;

class CurrencyService
{
    public function getBaseCurrency(int $companyId): Currency
    {
        $company = \Modules\Core\Models\Company::findOrFail($companyId);
        
        return $company->baseCurrency ?? Currency::where('code', 'BDT')->first();
    }

    public function convert(float $amount, int $fromCurrencyId, int $toCurrencyId, ?float $exchangeRate = null): float
    {
        if ($fromCurrencyId === $toCurrencyId) {
            return $amount;
        }

        if (!$exchangeRate) {
            $exchangeRate = app(ExchangeRateService::class)->getRate($fromCurrencyId, $toCurrencyId);
        }

        if (!$exchangeRate) {
            throw new InvalidArgumentException('Exchange rate not available for the specified currencies.');
        }

        return round($amount * $exchangeRate, 4);
    }

    public function formatAmount(float $amount, int $currencyId, bool $includeSymbol = true): string
    {
        $currency = Currency::findOrFail($currencyId);
        
        $formatted = number_format($amount, $currency->decimal_places ?? 2);

        if ($includeSymbol) {
            $formatted = $currency->symbol . ' ' . $formatted;
        }

        return $formatted;
    }

    public function getCurrencyById(int $currencyId): Currency
    {
        return Currency::findOrFail($currencyId);
    }

    public function getCurrencyByCode(string $code): Currency
    {
        return Currency::where('code', strtoupper($code))->firstOrFail();
    }
}
