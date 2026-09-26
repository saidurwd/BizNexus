<?php

use Modules\Core\Models\Company;
use Modules\Core\Models\CostCenter;

test('cost centers are only visible in their own company', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    CostCenter::withoutGlobalScopes()->create(['company_id' => $company->id, 'code' => 'CC-OWN', 'name' => 'Own Sales', 'status' => 'active']);
    CostCenter::withoutGlobalScopes()->create(['company_id' => $otherCompany->id, 'code' => 'CC-OTHER', 'name' => 'Other Sales', 'status' => 'active']);

    actingInCompany(companyUser(['finance.costcenters.view'], $company), $company)
        ->get(route('finance.cost-centers.index'))
        ->assertOk()
        ->assertSee('Own Sales')
        ->assertDontSee('Other Sales');
});
