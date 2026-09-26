<?php

use Illuminate\Support\Carbon;
use Modules\Core\Models\Company;
use Modules\Core\Models\FiscalYear;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\DocumentNumberService;

beforeEach(function () {
    Carbon::setTestNow('2026-06-15 10:00:00');
    $this->company = Company::factory()->create();
    $this->admin = companyUser(['finance.number-series.view', 'finance.number-series.manage'], $this->company);
    $this->numbers = app(DocumentNumberService::class);
});

test('a company sets its own prefix, format and next number, and new documents follow it', function () {
    $this->numbers->generateNumber($this->company->id, 'CI');

    actingInCompany($this->admin, $this->company)
        ->put(route('finance.number-series.update', 'CI'), ['prefix' => 'INV', 'format' => '{PREFIX}/{YY}/{SEQUENCE:4}', 'next_number' => 500])
        ->assertSessionHasNoErrors();

    expect($this->numbers->generateNumber($this->company->id, 'CI', documentDate: now()))->toBe('INV/26/0500');

    actingInCompany($this->admin, $this->company)->get(route('finance.number-series.index'))->assertOk()->assertSee('INV/26/0501');
});

test('the counter cannot move backwards and formats need a sequence token', function () {
    $this->numbers->generateNumber($this->company->id, 'CI');
    $this->numbers->generateNumber($this->company->id, 'CI');

    actingInCompany($this->admin, $this->company)
        ->put(route('finance.number-series.update', 'CI'), ['prefix' => 'CI', 'format' => '{PREFIX}-{SEQUENCE:6}', 'next_number' => 2])
        ->assertSessionHasErrors('next_number');

    actingInCompany($this->admin, $this->company)
        ->put(route('finance.number-series.update', 'CI'), ['prefix' => 'CI', 'format' => '{PREFIX}-{YEAR}', 'next_number' => 3])
        ->assertSessionHasErrors('format');

    actingInCompany($this->admin, $this->company)
        ->put(route('finance.number-series.update', 'CI'), ['prefix' => 'CI', 'format' => '<script>{SEQUENCE:6}', 'next_number' => 3])
        ->assertSessionHasErrors('format');
});

test('a new fiscal year journal series keeps the company settings', function () {
    app(CompanyContextService::class)->pinCompany($this->company->id);
    actingInCompany($this->admin, $this->company)
        ->put(route('finance.number-series.update', 'JV'), ['prefix' => 'GJ', 'format' => '{PREFIX}{YEAR}-{SEQUENCE:5}', 'next_number' => 1])
        ->assertSessionHasNoErrors();

    $fiscalYear = FiscalYear::create(['company_id' => $this->company->id, 'name' => 'FY2027', 'start_date' => '2027-01-01', 'end_date' => '2027-12-31', 'status' => 'OPEN']);

    expect($this->numbers->generateNumber($this->company->id, 'JV', $fiscalYear->id, Carbon::parse('2027-01-05')))->toBe('GJ2027-00001');
});
