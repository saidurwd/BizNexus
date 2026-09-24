<?php

use Modules\Core\Models\AuditLog;
use Modules\Core\Models\Branch;
use Modules\Core\Models\Company;
use Modules\Core\Models\UserBranch;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->otherCompany = Company::factory()->create();
    $this->user = companyUser([], $this->company);
    companyUserRole($this->user, $this->otherCompany);
});

test('switching company updates the active company and records an audit entry', function () {
    actingInCompany($this->user, $this->company)
        ->post(route('company.switch'), ['company_id' => $this->otherCompany->id])
        ->assertSessionHas('active_company_id', $this->otherCompany->id);

    $entry = AuditLog::where('action', 'COMPANY_SWITCH')->sole();
    expect($entry->user_id)->toBe($this->user->id)
        ->and($entry->company_id)->toBe($this->otherCompany->id)
        ->and($entry->old_values)->toBe(['company_id' => $this->company->id])
        ->and($entry->new_values)->toBe(['company_id' => $this->otherCompany->id]);
});

test('switching to a company without access keeps the current company', function () {
    $inaccessible = Company::factory()->create();

    actingInCompany($this->user, $this->company)
        ->post(route('company.switch'), ['company_id' => $inaccessible->id])
        ->assertSessionHas('error', 'You do not have access to the selected company.')
        ->assertSessionHas('active_company_id', $this->company->id);
});

test('switching with a branch of another company keeps the current company and branch', function () {
    $currentBranch = Branch::create(['company_id' => $this->company->id, 'code' => 'HQ', 'name' => 'Head Office', 'status' => 'active']);
    UserBranch::create(['user_id' => $this->user->id, 'company_id' => $this->company->id, 'branch_id' => $currentBranch->id, 'status' => 'active']);

    actingInCompany($this->user, $this->company)
        ->withSession(['active_company_id' => $this->company->id, 'active_branch_id' => $currentBranch->id])
        ->post(route('company.switch'), ['company_id' => $this->otherCompany->id, 'branch_id' => $currentBranch->id])
        ->assertSessionHas('error', 'You do not have access to the selected branch.')
        ->assertSessionHas('active_company_id', $this->company->id)
        ->assertSessionHas('active_branch_id', $currentBranch->id);

    expect(AuditLog::where('action', 'COMPANY_SWITCH')->exists())->toBeFalse();
});
