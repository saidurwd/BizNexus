<?php

namespace Modules\Finance\Services;

use Modules\Core\Models\Currency;
use Modules\Core\Models\ExchangeRate;
use Carbon\Carbon;
use InvalidArgumentException;
use Modules\Core\Services\AuditService;

class ExchangeRateService
{
    public function __construct(protected AuditService $audit) {}

    public function getRate(int $fromCurrencyId, int $toCurrencyId, ?Carbon $date = null): ?float
    {
        $date = $date ?? Carbon::today();

        $rate = ExchangeRate::where('currency_id', $fromCurrencyId)
            ->where('rate_date', '<=', $date)
            ->where('status', 'active')
            ->orderByDesc('rate_date')
            ->first();

        if (!$rate) {
            return null;
        }

        if ($fromCurrencyId === $toCurrencyId) {
            return 1.0;
        }

        $targetRate = ExchangeRate::where('currency_id', $toCurrencyId)
            ->where('rate_date', '<=', $date)
            ->where('status', 'active')
            ->orderByDesc('rate_date')
            ->first();

        if (!$targetRate) {
            return null;
        }

        return round($targetRate->exchange_rate / $rate->exchange_rate, 6);
    }

    public function updateRate(int $companyId, int $currencyId, float $rate, ?Carbon $rateDate = null): ExchangeRate
    {
        $rateDate = $rateDate ?? Carbon::today();

        $rate = ExchangeRate::updateOrCreate(
            [
                'company_id' => $companyId,
                'currency_id' => $currencyId,
                'rate_date' => $rateDate,
            ],
            [
                'exchange_rate' => $rate,
                'source' => 'manual',
                'status' => 'active',
            ]
        );

        $this->audit->logCustom('Finance', 'ExchangeRate', $rate->id, $rate->wasRecentlyCreated ? 'CREATE' : 'UPDATE', $rate->toArray());

        return $rate;
    }

    public function getHistoricalRate(int $currencyId, Carbon $date): ?float
    {
        $rate = ExchangeRate::where('currency_id', $currencyId)
            ->where('rate_date', '<=', $date)
            ->where('status', 'active')
            ->orderByDesc('rate_date')
            ->first();

        return $rate?->exchange_rate;
    }

    public function convertToBase(float $amount, int $currencyId, ?Carbon $date = null): float
    {
        $currency = Currency::findOrFail($currencyId);
        
        if ($currency->is_base) {
            return $amount;
        }

        $rate = $this->getHistoricalRate($currencyId, $date ?? Carbon::today());
        
        if (!$rate) {
            throw new InvalidArgumentException('Exchange rate not available for the specified currency.');
        }

        return round($amount * $rate, 4);
    }
}
