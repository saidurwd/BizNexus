<?php

use Illuminate\Database\QueryException;
use Modules\Core\Models\Company;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\Journal;
use Modules\Finance\Models\JournalLine;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->account = Account::factory()->create(['company_id' => $this->company->id]);
    $this->journal = Journal::factory()->posted()->create(['company_id' => $this->company->id]);
    JournalLine::factory()->create(['journal_id' => $this->journal->id, 'account_id' => $this->account->id]);
});

test('deleting a company with journals is refused and keeps the journals', function () {
    $user = companyUser(['core.companies.delete'], $this->company);

    actingInCompany($user, $this->company)
        ->delete(route('core.companies.destroy', $this->company->id))
        ->assertSessionHas('error', 'This company has financial records and cannot be deleted. Deactivate it instead.');

    expect(Company::find($this->company->id))->not->toBeNull()
        ->and(Journal::withoutGlobalScopes()->whereKey($this->journal->id)->exists())->toBeTrue();
});

test('an account with journal lines cannot be removed from the database', function () {
    $this->account->forceDelete();
})->throws(QueryException::class);

test('a journal with lines cannot be removed from the database', function () {
    Journal::withoutGlobalScopes()->whereKey($this->journal->id)->delete();
})->throws(QueryException::class);
