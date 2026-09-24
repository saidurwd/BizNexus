<?php

namespace Modules\Finance\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Modules\Core\Models\Company;
use Modules\Core\Models\Currency;
use Modules\Core\Models\ExchangeRate;
use Modules\Core\Scopes\CompanyScope;
use Modules\Core\Services\AuditService;
use Modules\Core\Support\Money;
use Modules\Finance\Enums\ExchangeRateType;
use Modules\Finance\Exceptions\MissingExchangeRateException;

/**
 * The single source of exchange rates. A rate is the company's functional (base) currency units per one
 * unit of a foreign currency, as of a date, for a rate type (IAS 21).
 */
class ExchangeRateService
{
    public function __construct(protected AuditService $audit) {}

    /**
     * The rate in force on the date: the latest active rate of the type on or before it. Spot rates older
     * than finance.fx.max_spot_rate_age_days are refused instead of being used silently.
     */
    public function rate(Company $company, Currency $currency, CarbonInterface $date, ExchangeRateType $type = ExchangeRateType::Spot): string
    {
        if ((int) $currency->id === (int) $company->base_currency_id) {
            return '1';
        }

        $rate = ExchangeRate::withoutGlobalScope(CompanyScope::class)
            ->where('company_id', $company->id)
            ->where('currency_id', $currency->id)
            ->where('rate_type', $type->value)
            ->where('status', 'active')
            ->whereDate('rate_date', '<=', $date->toDateString())
            ->orderByDesc('rate_date')
            ->first();

        $maxAge = (int) config('finance.fx.max_spot_rate_age_days', 7);

        if (! $rate || ($type === ExchangeRateType::Spot && $rate->rate_date->diffInDays($date) > $maxAge)) {
            throw new MissingExchangeRateException(
                "No {$type->value} rate for {$currency->code} on {$date->toDateString()} in company {$company->code}."
            );
        }

        return (string) $rate->exchange_rate;
    }

    /**
     * Convert a foreign amount into the company's functional currency.
     */
    public function toFunctional(Money $amount, Company $company, CarbonInterface $date, ExchangeRateType $type = ExchangeRateType::Spot): Money
    {
        $currency = Currency::where('code', $amount->currency)->firstOrFail();

        return $amount->convertedTo($company->baseCurrency->code, $this->rate($company, $currency, $date, $type));
    }

    /**
     * Rate to store on a new document: 1 for the functional currency (or no currency), otherwise the spot rate.
     */
    public function rateForDocument(Company|int $company, ?int $currencyId, CarbonInterface|string $date): string
    {
        $company = $company instanceof Company ? $company : Company::findOrFail($company);

        if ($currencyId === null || (int) $currencyId === (int) $company->base_currency_id) {
            return '1';
        }

        return $this->rate($company, Currency::findOrFail($currencyId), Carbon::parse($date));
    }

    public function record(Company $company, Currency $currency, CarbonInterface $date, string $rate, ExchangeRateType $type = ExchangeRateType::Spot, string $source = 'manual'): ExchangeRate
    {
        $exchangeRate = ExchangeRate::withoutGlobalScope(CompanyScope::class)->updateOrCreate(
            ['company_id' => $company->id, 'currency_id' => $currency->id, 'rate_type' => $type->value, 'rate_date' => Carbon::parse($date)->toDateString()],
            ['exchange_rate' => $rate, 'source' => $source, 'status' => 'active']
        );

        $this->audit->logCustom('Finance', 'ExchangeRate', $exchangeRate->id, $exchangeRate->wasRecentlyCreated ? 'CREATE' : 'UPDATE', $exchangeRate->toArray(), $company->id);

        return $exchangeRate;
    }
}
