<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Models\Company;

beforeEach(function () {
    Storage::fake('public');
    $this->company = Company::factory()->create(['name' => 'Acme', 'legal_name' => 'Acme Trading GmbH', 'address' => "Hauptstrasse 1\nBerlin", 'email' => 'office@acme.test', 'phone' => '+49 30 1234']);
    $this->admin = companyUser(['core.companies.update', 'finance.reports.view', 'finance.reports.export'], $this->company);
});

function companyForm(Company $company, array $overrides = []): array
{
    return [
        'code' => $company->code, 'name' => $company->name, 'legal_name' => $company->legal_name, 'address' => $company->address,
        'email' => $company->email, 'phone' => $company->phone, 'status' => 'active', ...$overrides,
    ];
}

test('an administrator uploads, replaces and removes the company logo', function () {
    actingInCompany($this->admin, $this->company)
        ->put(route('core.companies.update', $this->company->id), companyForm($this->company, ['logo' => UploadedFile::fake()->image('logo.png', 400, 120), 'website' => 'https://acme.test', 'mobile' => '+49 170 5555']))
        ->assertSessionHasNoErrors();

    $first = $this->company->fresh()->logo_path;
    Storage::disk('public')->assertExists($first);
    expect($this->company->fresh())->website->toBe('https://acme.test')->mobile->toBe('+49 170 5555');

    actingInCompany($this->admin, $this->company)->put(route('core.companies.update', $this->company->id), companyForm($this->company, ['logo' => UploadedFile::fake()->image('new-logo.jpg', 300, 100)]));
    Storage::disk('public')->assertMissing($first);

    actingInCompany($this->admin, $this->company)->put(route('core.companies.update', $this->company->id), companyForm($this->company, ['remove_logo' => 1]));
    expect($this->company->fresh()->logo_path)->toBeNull();
});

test('only raster images are accepted as a logo', function () {
    actingInCompany($this->admin, $this->company)
        ->put(route('core.companies.update', $this->company->id), companyForm($this->company, ['logo' => UploadedFile::fake()->create('logo.svg', 5, 'image/svg+xml')]))
        ->assertSessionHasErrors('logo');
});

test('reports, PDFs and exports carry the company letterhead', function () {
    $this->company->update(['website' => 'https://acme.test', 'logo_path' => UploadedFile::fake()->image('logo.png')->store("company-logos/{$this->company->id}", 'public')]);

    actingInCompany($this->admin, $this->company)->get(route('finance.reports.trial-balance'))
        ->assertOk()
        ->assertSeeInOrder([$this->company->fresh()->logoUrl(), 'Acme Trading GmbH', 'Hauptstrasse 1', 'office@acme.test', 'https://acme.test', 'Trial Balance'], false);

    expect($this->company->fresh()->logoDataUri())->toStartWith('data:image/png;base64,');

    $workbook = actingInCompany($this->admin, $this->company)->get(route('finance.reports.export', ['report' => 'trial-balance']))->streamedContent();
    $path = tempnam(sys_get_temp_dir(), 'xlsx');
    file_put_contents($path, $workbook);
    $zip = new ZipArchive;
    $zip->open($path);
    $strings = $zip->getFromName('xl/sharedStrings.xml').$zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    unlink($path);

    expect($strings)->toContain('Acme Trading GmbH')->toContain('https://acme.test');
});
