<?php

use Modules\Core\Models\Company;
use Modules\Finance\Controllers\Web\ReportExportController;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\Journal;
use Modules\Finance\Models\JournalLine;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $bank = Account::factory()->asset()->create(['company_id' => $this->company->id, 'account_code' => '01100']);
    $sales = Account::factory()->revenue()->create(['company_id' => $this->company->id]);
    $journal = Journal::factory()->create(['company_id' => $this->company->id, 'status' => Journal::STATUS_POSTED, 'journal_date' => now()]);
    JournalLine::factory()->create(['journal_id' => $journal->id, 'account_id' => $bank->id, 'debit' => 250, 'credit' => 0]);
    JournalLine::factory()->create(['journal_id' => $journal->id, 'account_id' => $sales->id, 'debit' => 0, 'credit' => 250]);
});

test('every financial report exports as an Excel workbook', function (string $report) {
    $response = actingInCompany(companyUser(['finance.reports.view', 'finance.reports.export'], $this->company), $this->company)
        ->get(route('finance.reports.export', ['report' => $report]));

    $response->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expect($response->streamedContent())->toStartWith('PK');
})->with(ReportExportController::REPORTS);

test('exporting needs the export permission', function () {
    actingInCompany(companyUser(['finance.reports.view'], $this->company), $this->company)
        ->get(route('finance.reports.export', ['report' => 'trial-balance']))
        ->assertForbidden();
});

test('the trial balance export keeps account codes as text and amounts as numbers', function () {
    $content = actingInCompany(companyUser(['finance.reports.view', 'finance.reports.export'], $this->company), $this->company)
        ->get(route('finance.reports.export', ['report' => 'trial-balance']))
        ->streamedContent();

    $path = tempnam(sys_get_temp_dir(), 'xlsx');
    file_put_contents($path, $content);
    $zip = new ZipArchive;
    $zip->open($path);
    $sheet = $zip->getFromName('xl/worksheets/sheet1.xml').$zip->getFromName('xl/sharedStrings.xml');
    $zip->close();
    unlink($path);

    expect($sheet)->toContain('01100')->toContain('<v>250</v>');
});
