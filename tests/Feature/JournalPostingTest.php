<?php

use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Models\Company;
use Modules\Core\Models\FiscalPeriod;
use Modules\Core\Models\FiscalYear;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\Journal;
use Modules\Finance\Services\JournalService;

beforeEach(function () {
    $this->company = Company::factory()->create();
    app(CompanyContextService::class)->pinCompany($this->company->id);
    $this->cash = Account::factory()->asset()->create(['company_id' => $this->company->id]);
    $this->revenue = Account::factory()->revenue()->create(['company_id' => $this->company->id]);
    $this->journals = app(JournalService::class);
});

function openPeriod(Company $company, int $year, string $status = 'OPEN'): FiscalPeriod
{
    $fiscalYear = FiscalYear::create([
        'company_id' => $company->id,
        'name' => "FY{$year}",
        'start_date' => "{$year}-01-01",
        'end_date' => "{$year}-12-31",
        'status' => 'OPEN',
    ]);

    return FiscalPeriod::create([
        'fiscal_year_id' => $fiscalYear->id,
        'period_name' => "{$year}",
        'period_number' => 1,
        'start_date' => "{$year}-01-01",
        'end_date' => "{$year}-12-31",
        'status' => $status,
    ]);
}

function draftJournal(?string $date = null): Journal
{
    return test()->journals->create([
        'journal_date' => $date ?? now()->toDateString(),
        'description' => 'Cash sale',
        'lines' => [
            ['account_id' => test()->cash->id, 'debit' => 500, 'credit' => 0],
            ['account_id' => test()->revenue->id, 'debit' => 0, 'credit' => 500],
        ],
    ]);
}

function approvedJournal(?string $date = null): Journal
{
    $journal = draftJournal($date);
    $journal = test()->journals->submit($journal);

    return test()->journals->approve($journal);
}

test('a draft journal gets a draft reference instead of an official number', function () {
    $journal = draftJournal();

    expect($journal->journal_number)->toBe(sprintf('DRAFT-%06d', $journal->id));
});

test('official numbers are issued on posting without gaps from deleted drafts', function () {
    openPeriod($this->company, now()->year);
    $this->journals->delete(draftJournal());
    $first = approvedJournal();
    $second = approvedJournal();

    expect($this->journals->post($second)->journal_number)->toBe('JV-'.now()->year.'-000001')
        ->and($this->journals->post($first)->journal_number)->toBe('JV-'.now()->year.'-000002');
});

test('the number takes its year from the journal date and each fiscal year has its own sequence', function () {
    $lastYear = now()->year - 1;
    openPeriod($this->company, $lastYear);
    openPeriod($this->company, now()->year);

    $lastYearJournal = $this->journals->post(approvedJournal("{$lastYear}-06-30"));
    $thisYearJournal = $this->journals->post(approvedJournal());

    expect($lastYearJournal->journal_number)->toBe("JV-{$lastYear}-000001")
        ->and($thisYearJournal->journal_number)->toBe('JV-'.now()->year.'-000001');
});

test('a journal cannot use an account of another company', function () {
    $foreignAccount = app(CompanyContextService::class)->runAs(
        $otherCompanyId = Company::factory()->create()->id,
        fn () => Account::factory()->asset()->create(['company_id' => $otherCompanyId])
    );

    $this->journals->addLine(draftJournal(), ['account_id' => $foreignAccount->id, 'debit' => 10]);
})->throws(InvalidAccountingTransactionException::class, "Journal lines must use accounts of the journal's company.");

test('the creator of a journal cannot approve it', function () {
    $user = companyUser(['finance.journals.approve'], $this->company);
    $this->actingAs($user);
    $journal = $this->journals->submit(draftJournal());

    actingInCompany($user, $this->company)
        ->post(route('finance.journals.approve', $journal->id))
        ->assertSessionHas('error', 'You cannot approve a journal you created.');

    expect($journal->fresh()->status)->toBe(Journal::STATUS_SUBMITTED);
});

test('another user can approve a journal and is recorded as approver', function () {
    $this->actingAs(companyUser([], $this->company));
    $journal = $this->journals->submit(draftJournal());
    $approver = companyUser([], $this->company);
    $this->actingAs($approver);

    $approved = $this->journals->approve($journal);

    expect($approved->approved_by)->toBe($approver->id)
        ->and($approved->approved_at)->not->toBeNull();
});

test('the approver cannot post the journal when that control is enabled', function () {
    config(['finance.controls.approver_cannot_post' => true]);
    openPeriod($this->company, now()->year);
    $this->actingAs(companyUser([], $this->company));
    $journal = $this->journals->submit(draftJournal());
    $this->actingAs(companyUser([], $this->company));
    $journal = $this->journals->approve($journal);

    $this->journals->post($journal);
})->throws(InvalidAccountingTransactionException::class, 'You cannot post a journal you approved.');

test('reversing posts the reversal and marks the original reversed together', function () {
    openPeriod($this->company, now()->year);
    $original = $this->journals->post(approvedJournal());

    $reversal = $this->journals->reverse($original, 'Wrong account');

    expect($reversal->status)->toBe(Journal::STATUS_POSTED)
        ->and($reversal->journal_number)->toBe('JV-'.now()->year.'-000002')
        ->and($reversal->lines()->sum('debit'))->toEqual(500)
        ->and($reversal->lines()->where('account_id', $this->revenue->id)->value('debit'))->toEqual(500)
        ->and($original->fresh()->status)->toBe(Journal::STATUS_REVERSED)
        ->and($original->fresh()->reversed_at)->not->toBeNull();
});

test('a reversal into a closed period leaves the original posted and creates nothing', function () {
    $lastYear = now()->year - 1;
    openPeriod($this->company, $lastYear);
    openPeriod($this->company, now()->year, status: 'CLOSED');
    $original = $this->journals->post(approvedJournal("{$lastYear}-06-30"));
    $journalCount = Journal::count();

    try {
        $this->journals->reverse($original, 'Wrong account');
    } catch (Throwable) {
    }

    expect($original->fresh()->status)->toBe(Journal::STATUS_POSTED)
        ->and(Journal::count())->toBe($journalCount);
});
