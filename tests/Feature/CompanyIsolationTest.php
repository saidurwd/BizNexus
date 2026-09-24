<?php

use Modules\Core\Exceptions\UnauthorizedCompanyAccessException;
use Modules\Core\Models\Company;
use Modules\Core\Models\UserCompany;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\Journal;
use Modules\Finance\Models\JournalLine;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->otherCompany = Company::factory()->create();
});

test('a user can view a journal of their active company', function () {
    $user = companyUser(['finance.journals.view'], $this->company);
    $journal = Journal::factory()->create(['company_id' => $this->company->id]);

    actingInCompany($user, $this->company)
        ->get(route('finance.journals.show', $journal->id))
        ->assertOk()
        ->assertSee($journal->journal_number);
});

test('a user cannot view a journal of another company by changing the id', function () {
    $user = companyUser(['finance.journals.view'], $this->company);
    $foreignJournal = Journal::factory()->create(['company_id' => $this->otherCompany->id]);

    actingInCompany($user, $this->company)
        ->get(route('finance.journals.show', $foreignJournal->id))
        ->assertNotFound();
});

test('a user cannot post a journal of another company', function () {
    $user = companyUser(['finance.journals.post'], $this->company);
    $foreignJournal = Journal::factory()->approved()->create(['company_id' => $this->otherCompany->id]);

    actingInCompany($user, $this->company)
        ->post(route('finance.journals.post', $foreignJournal->id))
        ->assertNotFound();

    expect(Journal::withoutGlobalScopes()->find($foreignJournal->id)->status)->toBe(Journal::STATUS_APPROVED);
});

test('company scoped queries return nothing when no company is active', function () {
    Account::factory()->create(['company_id' => $this->company->id]);

    expect(Account::count())->toBe(0)
        ->and(app(CompanyContextService::class)->runAs($this->company->id, fn () => Account::count()))->toBe(1);
});

test('a record cannot be created for another company while a company is active', function () {
    app(CompanyContextService::class)->runAs($this->company->id, function () {
        Account::factory()->create(['company_id' => $this->otherCompany->id]);
    });
})->throws(UnauthorizedCompanyAccessException::class);

test('a record cannot be moved to another company', function () {
    $account = Account::factory()->create(['company_id' => $this->company->id]);

    app(CompanyContextService::class)->runAs($this->company->id, function () use ($account) {
        $account->update(['company_id' => $this->otherCompany->id]);
    });
})->throws(UnauthorizedCompanyAccessException::class);

test('a journal line takes its company from its journal', function () {
    $journal = Journal::factory()->create(['company_id' => $this->company->id]);
    $account = Account::factory()->create(['company_id' => $this->company->id]);

    $line = JournalLine::factory()->create(['journal_id' => $journal->id, 'account_id' => $account->id]);

    expect($line->company_id)->toBe($this->company->id);
});

test('a journal line cannot belong to a different company than its journal', function () {
    $journal = Journal::factory()->create(['company_id' => $this->company->id]);

    JournalLine::factory()->create([
        'journal_id' => $journal->id,
        'company_id' => $this->otherCompany->id,
        'account_id' => Account::factory()->create(['company_id' => $this->otherCompany->id])->id,
    ]);
})->throws(UnauthorizedCompanyAccessException::class);

test('revoking company access removes it from an existing session', function () {
    $user = companyUser(['finance.journals.view'], $this->company);
    UserCompany::where('user_id', $user->id)->update(['status' => 'inactive']);

    actingInCompany($user, $this->company)
        ->get(route('finance.journals.index'))
        ->assertRedirect(route('company.selection'))
        ->assertSessionMissing('active_company_id');
});
