<?php

use Modules\Core\Models\Branch;
use Modules\Core\Models\Company;
use Modules\Core\Models\CompanyUserRole;
use Modules\Core\Models\UserBranch;
use Modules\Core\Models\UserCompany;
use Modules\Core\Services\BranchAccessService;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = companyUser([], $this->company);
    $this->headOffice = Branch::create(['company_id' => $this->company->id, 'code' => 'HQ', 'name' => 'Head Office', 'status' => 'active']);
    $this->sylhet = Branch::create(['company_id' => $this->company->id, 'code' => 'SYL', 'name' => 'Sylhet', 'status' => 'active']);
    UserBranch::create(['user_id' => $this->user->id, 'company_id' => $this->company->id, 'branch_id' => $this->headOffice->id, 'status' => 'active']);
    $this->branches = app(BranchAccessService::class);
});

test('without all-branches access a user only reaches assigned branches', function () {
    expect($this->branches->hasAccess($this->headOffice->id, $this->company->id, $this->user->id))->toBeTrue()
        ->and($this->branches->hasAccess($this->sylhet->id, $this->company->id, $this->user->id))->toBeFalse();
});

test('all-branches access covers every active branch including ones created later', function () {
    UserCompany::where('user_id', $this->user->id)->update(['all_branches' => true]);
    $newBranch = Branch::create(['company_id' => $this->company->id, 'code' => 'CTG', 'name' => 'Chittagong', 'status' => 'active']);
    $closedBranch = Branch::create(['company_id' => $this->company->id, 'code' => 'OLD', 'name' => 'Closed', 'status' => 'inactive']);

    expect($this->branches->getAccessibleBranches($this->company->id, $this->user->id)->pluck('id')->sort()->values()->all())
        ->toBe([$this->headOffice->id, $this->sylhet->id, $newBranch->id])
        ->and($this->branches->hasAccess($closedBranch->id, $this->company->id, $this->user->id))->toBeFalse();
});

test('all-branches access does not extend to another company', function () {
    UserCompany::where('user_id', $this->user->id)->update(['all_branches' => true]);
    $foreignBranch = Branch::create(['company_id' => Company::factory()->create()->id, 'code' => 'X', 'name' => 'Foreign', 'status' => 'active']);

    expect($this->branches->hasAccess($foreignBranch->id, $this->company->id, $this->user->id))->toBeFalse();
});

test('an administrator can grant all-branches access', function () {
    $admin = companyUser(['core.users.update'], $this->company);
    $role = CompanyUserRole::where('user_id', $this->user->id)->value('role_id');

    actingInCompany($admin, $this->company)
        ->put(route('core.users.update', $this->user->id), [
            'name' => $this->user->name,
            'email' => $this->user->email,
            'companies' => [$this->company->id],
            'all_branches' => [$this->company->id],
            'roles' => [$role],
        ])
        ->assertSessionHasNoErrors();

    expect(UserCompany::where('user_id', $this->user->id)->value('all_branches'))->toBeTrue();
});
