<?php

use Illuminate\Support\Carbon;
use Modules\Core\Models\Company;
use Modules\Finance\Enums\AccountPurpose;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\AccountMapping;
use Modules\Finance\Models\Journal;
use Modules\Finance\Models\JournalLine;

beforeEach(function () {
    Carbon::setTestNow('2026-06-15 10:00:00');
    $this->company = Company::factory()->create();
    $this->bank = Account::factory()->asset()->create(['company_id' => $this->company->id]);
    $this->revenue = Account::factory()->revenue()->create(['company_id' => $this->company->id]);
    AccountMapping::create(['company_id' => $this->company->id, 'purpose' => AccountPurpose::Bank, 'account_id' => $this->bank->id]);
});

function saleOnDate(Account $bank, Account $revenue, string $date, string $amount, string $status = Journal::STATUS_POSTED): void
{
    $journal = Journal::factory()->create(['company_id' => $bank->company_id, 'status' => $status, 'journal_date' => $date]);
    JournalLine::factory()->create(['journal_id' => $journal->id, 'account_id' => $bank->id, 'debit' => $amount, 'credit' => 0]);
    JournalLine::factory()->create(['journal_id' => $journal->id, 'account_id' => $revenue->id, 'debit' => 0, 'credit' => $amount]);
}

test('the dashboard shows ledger balances and profit for the fiscal year to date only', function () {
    saleOnDate($this->bank, $this->revenue, '2026-03-10', '1000');
    saleOnDate($this->bank, $this->revenue, '2025-11-20', '500');
    saleOnDate($this->bank, $this->revenue, '2026-04-01', '9999', Journal::STATUS_DRAFT);

    actingInCompany(companyUser(['finance.dashboard.view'], $this->company), $this->company)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeInOrder(['1,500.00', 'Cash and bank'])
        ->assertSeeInOrder(['1,000.00', 'Profit, fiscal year to date'])
        ->assertDontSee('9,999')
        ->assertDontSee('Conversion Rate');
});

test('users without the finance dashboard permission only see the work they can act on', function () {
    Journal::factory()->create(['company_id' => $this->company->id, 'status' => Journal::STATUS_SUBMITTED]);

    actingInCompany(companyUser(['finance.journals.approve'], $this->company), $this->company)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Journals awaiting approval')
        ->assertDontSee('Cash and bank')
        ->assertDontSee('Draft journals');
});
