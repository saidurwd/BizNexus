<?php

use Modules\Core\Models\Company;
use Modules\Core\Models\Currency;
use Modules\Finance\Enums\AccountPurpose;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\AccountMapping;
use Modules\Finance\Models\Journal;
use Modules\Finance\Models\JournalLine;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = companyUser(['finance.reports.view', 'finance.fx-revaluation.view', 'finance.fx-revaluation.run'], $this->company);
    $this->bankGl = Account::factory()->asset()->create(['company_id' => $this->company->id]);
    AccountMapping::create(['company_id' => $this->company->id, 'purpose' => AccountPurpose::Bank, 'account_id' => $this->bankGl->id]);
});

function journalOnAccount(Account $account, string $status, string $description): Journal
{
    $journal = Journal::factory()->create(['company_id' => $account->company_id, 'status' => $status, 'journal_date' => now(), 'description' => $description]);
    JournalLine::factory()->create(['journal_id' => $journal->id, 'account_id' => $account->id, 'description' => null]);

    return $journal;
}

test('the bank book lists posted journals on bank accounts only', function () {
    journalOnAccount($this->bankGl, Journal::STATUS_POSTED, 'Posted bank transfer');
    journalOnAccount($this->bankGl, Journal::STATUS_DRAFT, 'Draft bank transfer');

    actingInCompany($this->user, $this->company)
        ->get(route('finance.reports.bank-book'))
        ->assertOk()
        ->assertSee('Posted bank transfer')
        ->assertDontSee('Draft bank transfer');
});

test('the revaluation screen renders and reports a missing closing rate', function () {
    $foreignCompany = $this->company;
    $revenue = Account::factory()->revenue()->create(['company_id' => $foreignCompany->id]);
    $this->bankGl->update(['revalue_foreign_currency' => true]);
    $usd = Currency::create(['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'decimal_places' => 2, 'status' => 'active']);
    $journal = Journal::factory()->posted()->create(['company_id' => $foreignCompany->id, 'currency_id' => $usd->id, 'exchange_rate' => 1.1]);
    JournalLine::factory()->create(['journal_id' => $journal->id, 'account_id' => $this->bankGl->id, 'debit' => 110, 'credit' => 0, 'currency_debit' => 100, 'currency_credit' => 0]);
    JournalLine::factory()->create(['journal_id' => $journal->id, 'account_id' => $revenue->id, 'debit' => 0, 'credit' => 110, 'currency_debit' => 0, 'currency_credit' => 100]);

    actingInCompany($this->user, $this->company)->get(route('finance.fx-revaluations.index'))->assertOk()->assertSee('No revaluations yet.');

    actingInCompany($this->user, $this->company)
        ->post(route('finance.fx-revaluations.store'), ['revaluation_date' => now()->toDateString()])
        ->assertSessionHas('error');
});
