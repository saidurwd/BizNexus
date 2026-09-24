<?php

use Modules\Core\Models\AuditLog;
use Modules\Core\Models\Branch;
use Modules\Core\Models\Company;
use Modules\Core\Models\ExchangeRate;
use Modules\Core\Models\FiscalPeriod;
use Modules\Core\Models\FiscalYear;
use Modules\Finance\Models\Journal;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->otherCompany = Company::factory()->create();
});

function openPeriodFor(Company $company): FiscalPeriod
{
    $fiscalYear = FiscalYear::create([
        'company_id' => $company->id,
        'name' => 'FY'.now()->year,
        'start_date' => now()->startOfYear(),
        'end_date' => now()->endOfYear(),
        'status' => 'OPEN',
    ]);

    return FiscalPeriod::create([
        'fiscal_year_id' => $fiscalYear->id,
        'period_name' => now()->format('F'),
        'period_number' => now()->month,
        'start_date' => now()->startOfMonth(),
        'end_date' => now()->endOfMonth(),
        'status' => 'OPEN',
    ]);
}

test('a user cannot close, reopen or lock a period of another company', function (string $routeName) {
    $user = companyUser(['core.periods.close', 'core.periods.reopen', 'core.periods.lock'], $this->company);
    $foreignPeriod = openPeriodFor($this->otherCompany);

    actingInCompany($user, $this->company)
        ->post(route($routeName, $foreignPeriod->id))
        ->assertNotFound();

    expect($foreignPeriod->fresh()->status)->toBe('OPEN');
})->with(['core.periods.close', 'core.periods.reopen', 'core.periods.lock']);

test('a period with unposted journals cannot be closed', function () {
    $user = companyUser(['core.periods.close'], $this->company);
    $period = openPeriodFor($this->company);
    Journal::factory()->submitted()->create(['company_id' => $this->company->id, 'journal_date' => now()->startOfMonth()]);

    actingInCompany($user, $this->company)
        ->post(route('core.periods.close', $period->id))
        ->assertSessionHas('error', 'Cannot close period with unposted journals.');

    expect($period->fresh()->status)->toBe('OPEN');
});

test('a period without unposted journals can be closed', function () {
    $user = companyUser(['core.periods.close'], $this->company);
    $period = openPeriodFor($this->company);

    actingInCompany($user, $this->company)
        ->post(route('core.periods.close', $period->id))
        ->assertRedirect(route('core.periods.index'));

    expect($period->fresh()->status)->toBe('CLOSED');
});

test('a user cannot create a branch in another company', function () {
    $user = companyUser(['core.branches.create'], $this->company);

    actingInCompany($user, $this->company)
        ->post(route('core.branches.store'), [
            'company_id' => $this->otherCompany->id,
            'code' => 'FOREIGN',
            'name' => 'Foreign Branch',
            'status' => 'active',
        ])
        ->assertSessionHasErrors('company_id');

    expect(Branch::where('code', 'FOREIGN')->exists())->toBeFalse();
});

test('a user cannot edit a branch of another company', function () {
    $user = companyUser(['core.branches.update'], $this->company);
    $foreignBranch = Branch::create(['company_id' => $this->otherCompany->id, 'code' => 'B1', 'name' => 'Foreign', 'status' => 'active']);

    actingInCompany($user, $this->company)
        ->get(route('core.branches.edit', $foreignBranch->id))
        ->assertNotFound();
});

test('a user cannot edit a company in which they lack the permission', function () {
    $user = companyUser(['core.companies.update'], $this->company);

    actingInCompany($user, $this->company)
        ->get(route('core.companies.edit', $this->otherCompany->id))
        ->assertNotFound();

    actingInCompany($user, $this->company)
        ->get(route('core.companies.edit', $this->company->id))
        ->assertOk();
});

test('a user cannot change an exchange rate of another company', function () {
    $user = companyUser(['core.exchange-rates.delete'], $this->company);
    $foreignRate = ExchangeRate::create([
        'company_id' => $this->otherCompany->id,
        'currency_id' => $this->otherCompany->base_currency_id,
        'rate_date' => now(),
        'exchange_rate' => 110,
    ]);

    actingInCompany($user, $this->company)
        ->delete(route('core.exchange-rates.destroy', $foreignRate->id))
        ->assertNotFound();

    expect(ExchangeRate::withoutGlobalScopes()->find($foreignRate->id))->not->toBeNull();
});

test('the audit log only shows entries of the active company', function () {
    $user = companyUser(['core.audit.view'], $this->company);
    $ownEntry = AuditLog::create(['company_id' => $this->company->id, 'module' => 'Finance', 'entity_type' => 'OwnJournalEntity', 'action' => 'CREATE']);
    $foreignEntry = AuditLog::create(['company_id' => $this->otherCompany->id, 'module' => 'Finance', 'entity_type' => 'ForeignJournalEntity', 'action' => 'CREATE']);

    actingInCompany($user, $this->company)
        ->get(route('core.audit.index'))
        ->assertOk()
        ->assertSee('OwnJournalEntity')
        ->assertDontSee('ForeignJournalEntity');

    actingInCompany($user, $this->company)
        ->get(route('core.audit.show', $foreignEntry->id))
        ->assertNotFound();
});
