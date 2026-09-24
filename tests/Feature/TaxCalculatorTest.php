<?php

use Illuminate\Support\Carbon;
use Modules\Core\Models\Company;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Support\Money;
use Modules\Finance\Contracts\TaxCalculator;
use Modules\Finance\Models\Tax;

beforeEach(function () {
    $this->company = Company::factory()->create();
    app(CompanyContextService::class)->pinCompany($this->company->id);
    $this->calculator = app(TaxCalculator::class);
});

function makeTax(string $code, string $rate, array $attributes = []): Tax
{
    return Tax::factory()->create([
        'company_id' => test()->company->id, 'tax_code' => $code, 'tax_name' => $code, 'tax_type' => 'VAT',
        'rate' => $rate, 'is_inclusive' => false, 'is_recoverable' => true, ...$attributes,
    ]);
}

test('exclusive tax is added to the net amount', function () {
    $result = $this->calculator->calculate(makeTax('VAT15', '15'), Money::of('1000', 'USD'), now());

    expect($result->net->amount)->toBe('1000.00')
        ->and($result->totalTax()->amount)->toBe('150.00')
        ->and($result->gross()->amount)->toBe('1150.00');
});

test('inclusive tax is extracted so that net plus tax equals the price exactly', function (string $gross, string $net, string $tax) {
    $result = $this->calculator->calculate(makeTax('VAT15I', '15', ['is_inclusive' => true]), Money::of($gross, 'USD'), now());

    expect($result->net->amount)->toBe($net)
        ->and($result->totalTax()->amount)->toBe($tax)
        ->and($result->gross()->amount)->toBe(Money::of($gross, 'USD')->amount);
})->with([
    ['1150', '1000.00', '150.00'],
    ['100', '86.96', '13.04'],
]);

test('the rate in force on the document date applies', function () {
    $vat = makeTax('VAT', '15');
    $vat->rates()->where('effective_from', '1900-01-01')->update(['effective_to' => '2026-06-30']);
    $vat->rates()->create(['rate' => '17.5', 'effective_from' => '2026-07-01']);

    expect($this->calculator->calculate($vat, Money::of('100', 'USD'), Carbon::parse('2026-06-30'))->totalTax()->amount)->toBe('15.00')
        ->and($this->calculator->calculate($vat, Money::of('100', 'USD'), Carbon::parse('2026-07-01'))->totalTax()->amount)->toBe('17.50');
});

test('a tax group applies each component, compounding where flagged', function () {
    $group = makeTax('GRP', '0', ['is_group' => true]);
    $group->components()->attach(makeTax('BASE', '10')->id, ['sequence' => 1, 'is_compound' => false]);
    $group->components()->attach(makeTax('CMPD', '5')->id, ['sequence' => 2, 'is_compound' => true]);

    $result = $this->calculator->calculate($group->fresh(), Money::of('100', 'USD'), now());

    expect(array_map(fn ($component) => $component->amount->amount, $result->components))->toBe(['10.00', '5.50'])
        ->and($result->gross()->amount)->toBe('115.50');
});

test('an inclusive compound group resolves back to the original net', function () {
    $group = makeTax('GRPI', '0', ['is_group' => true, 'is_inclusive' => true]);
    $group->components()->attach(makeTax('B', '10')->id, ['sequence' => 1, 'is_compound' => false]);
    $group->components()->attach(makeTax('C', '5')->id, ['sequence' => 2, 'is_compound' => true]);

    $result = $this->calculator->calculate($group->fresh(), Money::of('115.50', 'USD'), now());

    expect($result->net->amount)->toBe('100.00')->and($result->totalTax()->amount)->toBe('15.50');
});

test('recoverable and non-recoverable components are separated', function () {
    $group = makeTax('MIX', '0', ['is_group' => true]);
    $group->components()->attach(makeTax('VATR', '10')->id, ['sequence' => 1, 'is_compound' => false]);
    $group->components()->attach(makeTax('LEVY', '2', ['is_recoverable' => false])->id, ['sequence' => 2, 'is_compound' => false]);

    $result = $this->calculator->calculate($group->fresh(), Money::of('100', 'USD'), now());

    expect($result->recoverableTax()->amount)->toBe('10.00')->and($result->nonRecoverableTax()->amount)->toBe('2.00');
});

test('tax follows the minor units of the document currency', function () {
    expect($this->calculator->calculate(makeTax('JCT', '10'), Money::of('1235', 'JPY'), now())->totalTax()->amount)->toBe('124');
});
