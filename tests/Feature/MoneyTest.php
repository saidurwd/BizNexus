<?php

use Modules\Core\Models\Currency;
use Modules\Core\Support\Money;

test('amounts are rounded half-up to the currency minor units', function (string $currency, string $amount, string $expected) {
    expect(Money::of($amount, $currency)->amount)->toBe($expected);
})->with([
    'two decimals' => ['USD', '10.005', '10.01'],
    'two decimals down' => ['USD', '10.004', '10.00'],
    'zero decimals' => ['JPY', '1234.5', '1235'],
    'three decimals' => ['KWD', '1.2345', '1.235'],
    'negative' => ['USD', '-10.005', '-10.01'],
]);

test('configured currencies take precedence over the ISO fallback', function () {
    Currency::create(['code' => 'XTS', 'name' => 'Test', 'symbol' => 'T', 'decimal_places' => 4, 'status' => 'active']);

    expect(Money::of('1.23456', 'XTS')->amount)->toBe('1.2346');
});

test('arithmetic is exact where floats are not', function () {
    $total = Money::of('0.1', 'USD')->plus(Money::of('0.2', 'USD'));

    expect($total->equals(Money::of('0.3', 'USD')))->toBeTrue()
        ->and(Money::of('100', 'USD')->minus(Money::of('0.01', 'USD'))->amount)->toBe('99.99');
});

test('conversion rounds to the target currency after exact multiplication', function () {
    expect(Money::of('1000', 'USD')->convertedTo('BDT', '109.87654321')->amount)->toBe('109876.54')
        ->and(Money::of('1000', 'USD')->convertedTo('JPY', '149.567')->amount)->toBe('149567')
        ->and(Money::of('3', 'KWD')->convertedTo('USD', '3.2551')->amount)->toBe('9.77');
});

test('different currencies cannot be combined', function () {
    Money::of('1', 'USD')->plus(Money::of('1', 'EUR'));
})->throws(InvalidArgumentException::class);

test('invalid amounts are rejected', function (mixed $amount) {
    Money::of($amount, 'USD');
})->with(['abc', '1e5', ''])->throws(InvalidArgumentException::class);

test('floats are converted without scientific notation', function () {
    expect(Money::of(0.00001, 'KWD')->amount)->toBe('0.000')
        ->and(Money::of(1234.5, 'USD')->amount)->toBe('1234.50');
});
