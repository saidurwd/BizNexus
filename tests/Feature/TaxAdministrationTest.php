<?php

use Illuminate\Support\Carbon;
use Modules\Core\Models\Company;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\Tax;
use Modules\Finance\Models\TaxRule;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = companyUser(['finance.taxes.view', 'finance.taxes.create', 'finance.taxes.update'], $this->company);
    $this->inputVat = app(CompanyContextService::class)->runAs($this->company->id, fn () => Account::factory()->asset()->create(['company_id' => $this->company->id]));
});

function scopedTax(): Tax
{
    return app(CompanyContextService::class)->runAs(test()->company->id, fn () => Tax::where('tax_code', 'VAT')->sole());
}

test('a tax code is created with its accounts, jurisdiction and first effective rate', function () {
    actingInCompany($this->user, $this->company)->post(route('finance.taxes.store'), [
        'tax_code' => 'VAT', 'tax_name' => 'Standard VAT', 'tax_type' => 'VAT', 'rate' => '21', 'effective_from' => '2026-01-01',
        'country_code' => 'NL', 'is_inclusive' => '0', 'is_recoverable' => '1', 'is_group' => '0',
        'input_account_id' => $this->inputVat->id, 'status' => 'active',
    ])->assertSessionHasNoErrors();

    $tax = scopedTax();
    expect($tax->input_account_id)->toBe($this->inputVat->id)
        ->and($tax->country_code)->toBe('NL')
        ->and($tax->rates()->sole()->effective_from->toDateString())->toBe('2026-01-01');
});

test('tax codes are unique per company', function () {
    $otherCompany = Company::factory()->create();
    app(CompanyContextService::class)->runAs($otherCompany->id, fn () => Tax::factory()->create(['company_id' => $otherCompany->id, 'tax_code' => 'VAT']));

    actingInCompany($this->user, $this->company)->post(route('finance.taxes.store'), [
        'tax_code' => 'VAT', 'tax_name' => 'VAT', 'tax_type' => 'VAT', 'rate' => '15', 'status' => 'active',
    ])->assertSessionHasNoErrors();
});

test('a rate change closes the previous rate and applies from its date', function () {
    $tax = app(CompanyContextService::class)->runAs($this->company->id, fn () => Tax::factory()->create(['company_id' => $this->company->id, 'tax_code' => 'VAT', 'rate' => 15]));

    actingInCompany($this->user, $this->company)
        ->post(route('finance.taxes.rates.store', $tax->id), ['rate' => '17.5', 'effective_from' => '2026-07-01'])
        ->assertSessionHasNoErrors();

    expect($tax->rateOn(Carbon::parse('2026-06-30')))->toEqual('15.0000')
        ->and($tax->rateOn(Carbon::parse('2026-07-01')))->toEqual('17.5000')
        ->and($tax->rates()->whereDate('effective_from', '1900-01-01')->value('effective_to'))->not->toBeNull();

    actingInCompany($this->user, $this->company)->get(route('finance.taxes.show', $tax->id))->assertOk()->assertSee('Rate history')->assertSee('17.5');
});

test('components are added to a tax group from its page', function () {
    [$group, $component] = app(CompanyContextService::class)->runAs($this->company->id, fn () => [
        Tax::factory()->create(['company_id' => $this->company->id, 'tax_code' => 'GRP', 'is_group' => true]),
        Tax::factory()->create(['company_id' => $this->company->id, 'tax_code' => 'CMP', 'rate' => 5]),
    ]);

    actingInCompany($this->user, $this->company)
        ->post(route('finance.taxes.components.store', $group->id), ['component_tax_id' => $component->id, 'sequence' => 1, 'is_compound' => '1'])
        ->assertSessionHasNoErrors();

    expect($group->components()->sole()->pivot->is_compound)->toBeTruthy();
    actingInCompany($this->user, $this->company)->get(route('finance.taxes.show', $group->id))->assertOk()->assertSee('Group components')->assertSee('CMP');
});

test('determination rules are maintained on the tax rules page', function () {
    actingInCompany($this->user, $this->company)->post(route('finance.tax-rules.store'), [
        'direction' => 'purchase', 'counterparty_country' => 'DE', 'counterparty_type' => 'b2b', 'supply_type' => 'services',
        'reverse_charge' => '1', 'priority' => 10,
    ])->assertSessionHasNoErrors();

    $rule = app(CompanyContextService::class)->runAs($this->company->id, fn () => TaxRule::sole());
    expect($rule->reverse_charge)->toBeTrue()->and($rule->counterparty_country)->toBe('DE');

    actingInCompany($this->user, $this->company)->get(route('finance.tax-rules.index'))->assertOk()->assertSee('B2B');
});
