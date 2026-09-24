<?php

use App\Models\User;
use Modules\Core\Models\ApprovalDelegation;
use Modules\Core\Models\Company;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;
use Modules\Finance\Models\Journal;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->manager = companyUser(['finance.journals.view', 'finance.journals.approve', 'finance.journals.post'], $this->company);
    $this->colleague = companyUser(['finance.journals.view'], $this->company);
});

function delegate(User $from, User $to, array $overrides = []): ApprovalDelegation
{
    return app(CompanyContextService::class)->runAs(test()->company->id, fn () => ApprovalDelegation::create([
        'delegator_id' => $from->id, 'delegate_id' => $to->id,
        'starts_on' => now()->toDateString(), 'ends_on' => now()->addWeek()->toDateString(),
        ...$overrides,
    ]));
}

function permissionsOf(User $user): array
{
    return (new PermissionService)->getUserPermissions($user->id, test()->company->id);
}

test('a delegate receives only the approval permissions of the delegator while the delegation is in force', function () {
    delegate($this->manager, $this->colleague);

    expect(permissionsOf($this->colleague))->toContain('finance.journals.approve')->not->toContain('finance.journals.post');
});

test('expired, future and revoked delegations grant nothing', function (array $overrides) {
    delegate($this->manager, $this->colleague, $overrides);

    expect(permissionsOf($this->colleague))->not->toContain('finance.journals.approve');
})->with([
    'expired' => [fn () => ['starts_on' => now()->subWeeks(2)->toDateString(), 'ends_on' => now()->subWeek()->toDateString()]],
    'future' => [fn () => ['starts_on' => now()->addDay()->toDateString()]],
    'revoked' => [fn () => ['revoked_at' => now()]],
]);

test('delegated authority cannot be delegated onward', function () {
    $third = companyUser(['finance.journals.view'], $this->company);
    delegate($this->manager, $this->colleague);
    delegate($this->colleague, $third);

    expect(permissionsOf($third))->not->toContain('finance.journals.approve');
});

test('a delegate can approve through the application', function () {
    delegate($this->manager, $this->colleague);
    $journal = Journal::factory()->submitted()->create(['company_id' => $this->company->id]);

    actingInCompany($this->colleague, $this->company)
        ->post(route('finance.journals.approve', $journal->id))
        ->assertSessionHas('success');

    expect($journal->fresh()->approved_by)->toBe($this->colleague->id);
});

test('a user delegates their approvals from the profile and can revoke them', function () {
    actingInCompany($this->manager, $this->company)
        ->post(route('core.approval-delegations.store'), [
            'delegate_id' => $this->colleague->id,
            'starts_on' => now()->toDateString(),
            'ends_on' => now()->addDays(5)->toDateString(),
            'reason' => 'Annual leave',
        ])
        ->assertSessionHasNoErrors();

    $delegation = ApprovalDelegation::withoutGlobalScopes()->sole();
    expect($delegation->delegator_id)->toBe($this->manager->id);

    actingInCompany($this->manager, $this->company)->delete(route('core.approval-delegations.destroy', $delegation->id));

    expect($delegation->fresh()->revoked_at)->not->toBeNull();
});

test('delegation is refused to users outside the company, beyond 90 days, or without approvals to give', function (Closure $payload, string $errorField) {
    actingInCompany($this->manager, $this->company)
        ->post(route('core.approval-delegations.store'), $payload())
        ->assertSessionHasErrors($errorField);

    expect(ApprovalDelegation::withoutGlobalScopes()->count())->toBe(0);
})->with([
    'outsider' => [fn () => ['delegate_id' => companyUser([], Company::factory()->create())->id, 'starts_on' => now()->toDateString(), 'ends_on' => now()->addDay()->toDateString()], 'delegate_id'],
    'too long' => [fn () => ['delegate_id' => test()->colleague->id, 'starts_on' => now()->toDateString(), 'ends_on' => now()->addDays(91)->toDateString()], 'ends_on'],
    'self' => [fn () => ['delegate_id' => test()->manager->id, 'starts_on' => now()->toDateString(), 'ends_on' => now()->addDay()->toDateString()], 'delegate_id'],
]);

test('delegation is refused when it would give the delegate conflicting permissions', function () {
    $approver = companyUser(['finance.payments.approve'], $this->company);
    $clerk = companyUser(['finance.suppliers.create'], $this->company);

    actingInCompany($approver, $this->company)
        ->post(route('core.approval-delegations.store'), [
            'delegate_id' => $clerk->id, 'starts_on' => now()->toDateString(), 'ends_on' => now()->addDay()->toDateString(),
        ])
        ->assertSessionHasErrors('delegate_id');
});

test('the profile page shows delegations', function () {
    delegate($this->manager, $this->colleague, ['reason' => 'Conference trip']);

    actingInCompany($this->manager, $this->company)->get(route('profile.edit'))->assertOk()->assertSee('Conference trip');
    actingInCompany($this->colleague, $this->company)->get(route('profile.edit'))->assertOk()->assertSee('approving on behalf of');
});
