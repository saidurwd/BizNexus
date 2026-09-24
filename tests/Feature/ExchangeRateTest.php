<?php

use Illuminate\Support\Carbon;
use Modules\Core\Models\Company;
use Modules\Core\Models\Currency;
use Modules\Core\Models\ExchangeRate;
use Modules\Core\Support\Money;
use Modules\Finance\Enums\ExchangeRateType;
use Modules\Finance\Exceptions\MissingExchangeRateException;
use Modules\Finance\Services\ExchangeRateService;

beforeEach(function () {
    $this->bdt = Currency::create(['code' => 'BDT', 'name' => 'Taka', 'symbol' => '৳', 'decimal_places' => 2, 'status' => 'active']);
    $this->usd = Currency::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar', 'symbol' => '$', 'decimal_places' => 2, 'status' => 'active']);
    $this->company = Company::factory()->create(['base_currency_id' => $this->bdt->id]);
    $this->rates = app(ExchangeRateService::class);
});

test('the rate in force is the latest of its type on or before the date', function () {
    $this->rates->record($this->company, $this->usd, Carbon::parse('2026-09-20'), '110.25');
    $this->rates->record($this->company, $this->usd, Carbon::parse('2026-09-22'), '110.50');
    $this->rates->record($this->company, $this->usd, Carbon::parse('2026-09-30'), '111.00', ExchangeRateType::Closing);

    expect($this->rates->rate($this->company, $this->usd, Carbon::parse('2026-09-23')))->toBe('110.50000000')
        ->and($this->rates->rate($this->company, $this->usd, Carbon::parse('2026-09-30'), ExchangeRateType::Closing))->toBe('111.00000000');
});

test('the functional currency always has a rate of one', function () {
    expect($this->rates->rate($this->company, $this->bdt, now()))->toBe('1');
});

test('rates of another company are never used', function () {
    $this->rates->record(Company::factory()->create(['base_currency_id' => $this->bdt->id]), $this->usd, now(), '120');

    $this->rates->rate($this->company, $this->usd, now());
})->throws(MissingExchangeRateException::class);

test('stale spot rates are refused', function () {
    $this->rates->record($this->company, $this->usd, now()->subDays(8), '110');

    $this->rates->rate($this->company, $this->usd, now());
})->throws(MissingExchangeRateException::class);

test('amounts convert to the functional currency with its precision', function () {
    $this->rates->record($this->company, $this->usd, now(), '109.87654321');

    expect($this->rates->toFunctional(Money::of('1000', 'USD'), $this->company, now())->amount)->toBe('109876.54');
});

test('a duplicate rate for the same currency, type and date is rejected', function () {
    $user = companyUser(['core.exchange-rates.create'], $this->company);
    $payload = ['currency_id' => $this->usd->id, 'rate_type' => 'spot', 'rate_date' => '2026-09-24', 'exchange_rate' => '110'];

    actingInCompany($user, $this->company)->post(route('core.exchange-rates.store'), $payload)->assertSessionHasNoErrors();
    actingInCompany($user, $this->company)->post(route('core.exchange-rates.store'), $payload)->assertSessionHasErrors('rate_date');
    actingInCompany($user, $this->company)->post(route('core.exchange-rates.store'), [...$payload, 'rate_type' => 'closing'])->assertSessionHasNoErrors();

    expect(ExchangeRate::withoutGlobalScopes()->count())->toBe(2);
});
