<?php

use Modules\Core\Models\Company;
use Modules\Core\Models\CompanyUserRole;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;
use Modules\Workflow\Models\WorkflowApproval;
use Modules\Workflow\Models\WorkflowDefinition;
use Modules\Workflow\Models\WorkflowInstance;
use Modules\Workflow\Services\WorkflowService;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = companyUser(['finance.journals.view'], $this->company);
    $this->assignment = CompanyUserRole::where('user_id', $this->user->id)->sole();
});

test('a role assignment only grants permissions within its validity window', function (?string $validFrom, ?string $validUntil, bool $granted) {
    $this->assignment->update(['valid_from' => $validFrom, 'valid_until' => $validUntil]);

    expect(app(PermissionService::class)->hasPermission('finance.journals.view', $this->user->id, $this->company->id))->toBe($granted);
})->with([
    'no window' => [null, null, true],
    'current window' => [fn () => now()->subDay()->toDateString(), fn () => now()->addDay()->toDateString(), true],
    'starts and ends today' => [fn () => now()->toDateString(), fn () => now()->toDateString(), true],
    'expired' => [null, fn () => now()->subDay()->toDateString(), false],
    'not yet started' => [fn () => now()->addDay()->toDateString(), null, false],
]);

test('pending approvals only include the active company even when the role exists in other companies', function () {
    $otherCompany = Company::factory()->create();
    $role = $this->assignment->role;
    CompanyUserRole::create(['user_id' => $this->user->id, 'company_id' => $otherCompany->id, 'role_id' => $role->id, 'status' => 'active']);
    $definition = WorkflowDefinition::create(['name' => 'Invoice', 'entity_type' => 'supplier_invoice', 'states' => [], 'transitions' => []]);

    $approvalFor = fn (Company $company) => WorkflowApproval::create([
        'workflow_instance_id' => WorkflowInstance::create([
            'company_id' => $company->id, 'workflow_definition_id' => $definition->id,
            'entity_type' => 'supplier_invoice', 'entity_id' => 1, 'current_state' => 'SUBMITTED',
        ])->id,
        'approver_type' => 'role', 'role' => $role->slug, 'status' => WorkflowApproval::STATUS_PENDING,
    ]);
    $ownApproval = $approvalFor($this->company);
    $approvalFor($otherCompany);
    $this->actingAs($this->user);

    $pending = app(CompanyContextService::class)->runAs($this->company->id, fn () => app(WorkflowService::class)->getPendingApprovals());

    expect($pending->flatten()->pluck('id')->all())->toBe([$ownApproval->id]);
});

test('a workflow instance of another company cannot be opened', function () {
    $otherCompany = Company::factory()->create();
    $user = companyUser(['core.workflow.view'], $this->company);
    $definition = WorkflowDefinition::create(['name' => 'Invoice', 'entity_type' => 'supplier_invoice', 'states' => [], 'transitions' => []]);
    $foreignInstance = WorkflowInstance::create([
        'company_id' => $otherCompany->id, 'workflow_definition_id' => $definition->id,
        'entity_type' => 'supplier_invoice', 'entity_id' => 1, 'current_state' => 'SUBMITTED',
    ]);

    actingInCompany($user, $this->company)
        ->get(route('workflow.show', $foreignInstance->id))
        ->assertNotFound();
});
