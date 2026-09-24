<?php

use Database\Seeders\DatabaseSeeder;
use Modules\Core\Models\Company;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Enums\AccountPurpose;
use Modules\Finance\Models\AccountMapping;

test('the demo data seeds a company with every automatic posting account mapped except intercompany', function () {
    $this->seed(DatabaseSeeder::class);

    $company = Company::where('code', 'DEMO')->sole();
    $mapped = app(CompanyContextService::class)->runAs($company->id, fn () => AccountMapping::pluck('purpose')->map->value->all());

    expect($mapped)->toEqualCanonicalizing(collect(AccountPurpose::cases())
        ->reject(fn (AccountPurpose $purpose) => in_array($purpose, [AccountPurpose::IntercompanyReceivable, AccountPurpose::IntercompanyPayable], true))
        ->map->value->values()->all());
});
