<?php

use Illuminate\Support\Carbon;
use Modules\Core\Models\Company;
use Modules\Core\Models\Currency;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Support\Formatter;
use Modules\Core\Support\Money;

beforeEach(function () {
    $this->company = Company::factory()->create(['locale' => 'en']);
    $this->user = companyUser(['finance.journals.view'], $this->company);
});

test('a right-to-left locale switches the layout direction', function () {
    $this->user->update(['locale' => 'ar']);

    actingInCompany($this->user, $this->company)->get(route('finance.journals.index'))
        ->assertOk()
        ->assertSee('lang="ar"', false)
        ->assertSee('dir="rtl"', false);
});

test('the company language applies when the user has none, and unsupported locales are ignored', function (?string $userLocale, string $companyLocale, string $expected) {
    $this->user->update(['locale' => $userLocale]);
    $this->company->update(['locale' => $companyLocale]);

    actingInCompany($this->user, $this->company)->get(route('finance.journals.index'))->assertSee('lang="'.$expected.'"', false);
})->with([
    'company default' => [null, 'fr', 'fr'],
    'user wins' => ['de', 'fr', 'de'],
    'unsupported user locale' => ['xx', 'es', 'es'],
]);

test('money is formatted for the locale with the currency minor units', function () {
    expect(Formatter::money(Money::of('1234.5', 'EUR'), locale: 'de'))->toBe("1.234,50\u{a0}€")
        ->and(Formatter::money(Money::of('1234.5', 'USD'), locale: 'en'))->toBe('$1,234.50')
        ->and(Formatter::money(Money::of('1234', 'JPY'), locale: 'en'))->toBe('¥1,234');
});

test('table amounts default to the company currency minor units and rates keep their precision', function () {
    $dinar = Currency::factory()->create(['code' => 'KWD', 'decimal_places' => 3]);
    $this->company->update(['base_currency_id' => $dinar->id]);

    $amounts = app(CompanyContextService::class)->runAs($this->company->id, fn () => [
        Formatter::amount('1234.5', locale: 'de'),
        Formatter::amount('1234.5', 'JPY', 'en'),
    ]);

    expect($amounts)->toBe(['1.234,500', '1,235'])
        ->and(Formatter::percent('7.5000', 'en'))->toBe('7.5')
        ->and(Formatter::rate('0.00671234', 'en'))->toBe('0.00671234');
});

test('the business date follows the company time zone', function () {
    Carbon::setTestNow('2026-09-26 11:00:00');
    $this->company->update(['timezone' => 'Pacific/Kiritimati']);

    $today = app(CompanyContextService::class)->runAs($this->company->id, fn () => app(CompanyContextService::class)->today());

    expect($today->toDateString())->toBe('2026-09-27');
});

test('users choose a supported language on their profile', function () {
    actingInCompany($this->user, $this->company)->patch('/profile', ['name' => $this->user->name, 'email' => $this->user->email, 'locale' => 'bn'])->assertSessionHasNoErrors();
    expect($this->user->fresh()->locale)->toBe('bn');

    actingInCompany($this->user, $this->company)->patch('/profile', ['name' => $this->user->name, 'email' => $this->user->email, 'locale' => 'xx'])->assertSessionHasErrors('locale');
});

test('a company time zone must be a valid identifier', function () {
    $admin = companyUser(['core.companies.update'], $this->company);

    actingInCompany($admin, $this->company)
        ->put(route('core.companies.update', $this->company->id), ['code' => $this->company->code, 'name' => $this->company->name, 'status' => 'active', 'timezone' => 'Mars/Olympus'])
        ->assertSessionHasErrors('timezone');
});
