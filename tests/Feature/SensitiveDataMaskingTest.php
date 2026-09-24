<?php

use Modules\Core\Models\Company;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\BankAccount;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->bankAccount = app(CompanyContextService::class)->runAs($this->company->id, fn () => BankAccount::create([
        'company_id' => $this->company->id,
        'bank_name' => 'City Bank',
        'account_name' => 'Operating',
        'account_number' => '0012345678901234',
        'currency_id' => $this->company->base_currency_id,
        'gl_account_id' => Account::factory()->asset()->create(['company_id' => $this->company->id])->id,
        'status' => 'active',
    ]));
});

test('bank account numbers are masked for users without the sensitive-data permission', function () {
    $user = companyUser(['finance.bank-accounts.view'], $this->company);

    actingInCompany($user, $this->company)
        ->get(route('finance.bank-accounts.index'))
        ->assertOk()
        ->assertSee('••••1234')
        ->assertDontSee('0012345678901234');
});

test('users with the sensitive-data permission see the full number', function () {
    $user = companyUser(['finance.bank-accounts.view', 'finance.bank-accounts.view-sensitive'], $this->company);

    actingInCompany($user, $this->company)
        ->get(route('finance.bank-accounts.index'))
        ->assertOk()
        ->assertSee('0012345678901234');
});

test('serialised bank accounts never contain the full number', function () {
    $this->actingAs(companyUser(['finance.bank-accounts.view'], $this->company));

    expect($this->bankAccount->toArray())
        ->not->toHaveKey('account_number')
        ->toHaveKey('display_account_number', '••••1234');
});
