<?php

namespace Modules\Finance\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\Exceptions\DuplicatePostingException;
use Modules\Core\Exceptions\InactiveAccountException;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Exceptions\NonPostableAccountException;
use Modules\Core\Exceptions\UnauthorizedCompanyAccessException;
use Modules\Core\Exceptions\UnbalancedJournalException;
use Modules\Core\Scopes\CompanyScope;
use Modules\Core\Services\AccountingPeriodService;
use Modules\Core\Services\AuditService;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\DocumentNumberService;
use Modules\Finance\Events\JournalPosted;
use Modules\Finance\Jobs\ProcessIntegrationJob;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\BudgetLine;
use Modules\Finance\Models\Journal;
use Modules\Finance\Models\JournalLine;
use Modules\Finance\Services\Concerns\EnforcesSegregationOfDuties;

class JournalService
{
    use EnforcesSegregationOfDuties;

    public function __construct(
        protected CompanyContextService $companyContext,
        protected AccountingPeriodService $periodService,
        protected DocumentNumberService $documentNumber,
        protected AuditService $audit
    ) {}

    /**
     * Create a draft journal in the active company. It receives a draft reference; the official
     * gapless number is only issued when the journal is posted.
     */
    public function create(array $data): Journal
    {
        return DB::transaction(function () use ($data) {
            $companyId = $this->companyContext->getActiveCompanyId();

            if ($companyId === null) {
                throw new InvalidAccountingTransactionException('A journal can only be created within an active company.');
            }

            if (isset($data['company_id']) && (int) $data['company_id'] !== $companyId) {
                throw new UnauthorizedCompanyAccessException((int) $data['company_id'], Auth::id());
            }

            if (count($data['lines']) < 2) {
                throw new InvalidAccountingTransactionException('Journal must have at least two lines.');
            }

            $journal = Journal::create([
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'] ?? null,
                'journal_number' => 'DRAFT-'.uniqid(),
                'journal_date' => $data['journal_date'],
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'description' => $data['description'] ?? null,
                'currency_id' => $data['currency_id'] ?? $this->companyContext->getBaseCurrency()?->id,
                'exchange_rate' => $data['exchange_rate'] ?? 1,
                'status' => Journal::STATUS_DRAFT,
                'created_by' => Auth::id(),
            ]);

            $journal->update(['journal_number' => $this->documentNumber->draftReference($journal->id)]);

            foreach ($data['lines'] as $lineData) {
                $this->addLine($journal, $lineData);
            }

            $journal->calculateTotals();
            $journal->save();

            if (! $journal->isBalanced()) {
                throw new UnbalancedJournalException((float) $journal->total_debit, (float) $journal->total_credit);
            }

            $this->audit->logCreate('Finance', 'Journal', $journal->id, $journal->toArray(), $companyId);

            return $journal;
        });
    }

    public function addLine(Journal $journal, array $lineData): JournalLine
    {
        if (! $journal->isDraft()) {
            throw new InvalidAccountingTransactionException('Can only add lines to draft journals.');
        }

        $this->ensureAccountCanReceivePosting($journal, (int) $lineData['account_id']);

        $line = $journal->lines()->create([
            'account_id' => $lineData['account_id'],
            'description' => $lineData['description'] ?? null,
            'debit' => $lineData['debit'] ?? 0,
            'credit' => $lineData['credit'] ?? 0,
            'currency_debit' => $lineData['currency_debit'] ?? $lineData['debit'] ?? 0,
            'currency_credit' => $lineData['currency_credit'] ?? $lineData['credit'] ?? 0,
            'cost_center_id' => $lineData['cost_center_id'] ?? null,
            'department_id' => $lineData['department_id'] ?? null,
            'branch_id' => $lineData['branch_id'] ?? null,
            'business_unit_id' => $lineData['business_unit_id'] ?? null,
            'project_id' => $lineData['project_id'] ?? null,
            'tax_id' => $lineData['tax_id'] ?? null,
            'reference' => $lineData['reference'] ?? null,
        ]);

        $journal->calculateTotals();
        $journal->save();

        return $line;
    }

    public function updateLine(JournalLine $line, array $data): JournalLine
    {
        $journal = $line->journal;

        if (! $journal->isDraft()) {
            throw new InvalidAccountingTransactionException('Can only update lines in draft journals');
        }

        if (isset($data['account_id'])) {
            $this->ensureAccountCanReceivePosting($journal, (int) $data['account_id']);
        }

        $line->update($data);

        $journal->calculateTotals();
        $journal->save();

        return $line;
    }

    public function removeLine(JournalLine $line): void
    {
        $journal = $line->journal;

        if (! $journal->isDraft()) {
            throw new InvalidAccountingTransactionException('Can only remove lines from draft journals');
        }

        $line->delete();

        $journal->calculateTotals();
        $journal->save();
    }

    public function validate(Journal $journal): void
    {
        $lines = $journal->lines()->with(['account' => fn ($query) => $query->withoutGlobalScope(CompanyScope::class)])->get();

        if ($lines->count() < 2) {
            throw new InvalidAccountingTransactionException('Journal must have at least 2 lines');
        }

        if (! $journal->isBalanced()) {
            throw new UnbalancedJournalException($journal->total_debit, $journal->total_credit);
        }

        foreach ($lines as $line) {
            if ($line->debit > 0 && $line->credit > 0) {
                throw new InvalidAccountingTransactionException('A line cannot have both debit and credit');
            }

            if ($line->debit == 0 && $line->credit == 0) {
                throw new InvalidAccountingTransactionException('A line must have either debit or credit');
            }

            if ($line->account === null || (int) $line->account->company_id !== (int) $journal->company_id) {
                throw new InvalidAccountingTransactionException('Journal lines must use existing accounts of the journal\'s company.');
            }

            $this->ensureAccountIsPostable($line->account);
        }
    }

    public function submit(Journal $journal): Journal
    {
        if (! $journal->canSubmit()) {
            throw new InvalidAccountingTransactionException('Journal cannot be submitted');
        }

        $this->validate($journal);

        $journal->update([
            'status' => Journal::STATUS_SUBMITTED,
            'submitted_by' => Auth::id(),
            'submitted_at' => now(),
            'updated_by' => Auth::id(),
        ]);

        $this->audit->logCustom('Finance', 'Journal', $journal->id, 'SUBMIT', $journal->toArray());

        return $journal->fresh();
    }

    public function approve(Journal $journal): Journal
    {
        if (! $journal->canApprove()) {
            throw new InvalidAccountingTransactionException('Journal cannot be approved');
        }

        $this->ensureApproverIsNotCreator($journal, 'journal');

        $journal->update([
            'status' => Journal::STATUS_APPROVED,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'updated_by' => Auth::id(),
        ]);

        $this->audit->logCustom('Finance', 'Journal', $journal->id, 'APPROVE', $journal->toArray());

        return $journal->fresh();
    }

    public function reject(Journal $journal, ?string $reason = null): Journal
    {
        if (! $journal->canApprove()) {
            throw new InvalidAccountingTransactionException('Journal cannot be rejected');
        }

        $journal->update([
            'status' => Journal::STATUS_REJECTED,
            'description' => $journal->description."\n\nRejected: ".($reason ?? 'No reason provided'),
            'updated_by' => Auth::id(),
        ]);

        $this->audit->logCustom('Finance', 'Journal', $journal->id, 'REJECT', ['reason' => $reason]);

        return $journal->fresh();
    }

    /**
     * Post an approved journal. The journal row is locked and re-read inside the transaction, so two
     * concurrent posts cannot both succeed, and the official number is issued only when posting commits.
     */
    public function post(Journal $journal): Journal
    {
        $posted = DB::transaction(function () use ($journal) {
            $journal = Journal::whereKey($journal->id)->lockForUpdate()->firstOrFail();

            if ($journal->isPosted() || $journal->isReversed()) {
                throw new DuplicatePostingException($journal);
            }

            if (! $journal->canPost()) {
                throw new InvalidAccountingTransactionException('Journal cannot be posted');
            }

            if (config('finance.controls.approver_cannot_post') && $journal->approved_by !== null && (int) $journal->approved_by === (int) Auth::id()) {
                throw new InvalidAccountingTransactionException('You cannot post a journal you approved.');
            }

            $this->postLocked($journal);

            $this->audit->logCustom('Finance', 'Journal', $journal->id, 'POST', $journal->toArray());

            return $journal;
        });

        ProcessIntegrationJob::dispatch(JournalPosted::class, ['journal' => $posted])->afterCommit();

        return $posted->fresh();
    }

    /**
     * Create and post the journal of a source document (invoice, payment, receipt) that went through its own
     * approval. The journal is recorded as approved by the posting user; segregation of duties is enforced
     * on the source document, not on this system-generated journal.
     */
    public function postFromSource(array $data): Journal
    {
        $posted = DB::transaction(function () use ($data) {
            $journal = $this->create($data);

            $journal->update([
                'status' => Journal::STATUS_APPROVED,
                'submitted_by' => Auth::id(),
                'submitted_at' => now(),
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            $this->postLocked($journal);

            $this->audit->logCustom('Finance', 'Journal', $journal->id, 'POST', $journal->toArray());

            return $journal;
        });

        ProcessIntegrationJob::dispatch(JournalPosted::class, ['journal' => $posted])->afterCommit();

        return $posted->fresh();
    }

    /**
     * Reverse a posted journal by posting a mirror journal dated $reversalDate (default today) in the same
     * transaction that marks the original as reversed, so the ledger never shows one without the other.
     */
    public function reverse(Journal $journal, ?string $reason = null, ?string $reversalDate = null): Journal
    {
        return DB::transaction(function () use ($journal, $reason, $reversalDate) {
            $journal = Journal::whereKey($journal->id)->lockForUpdate()->firstOrFail();

            if (! $journal->canReverse()) {
                throw new InvalidAccountingTransactionException('Journal cannot be reversed');
            }

            $reversal = Journal::create([
                'company_id' => $journal->company_id,
                'branch_id' => $journal->branch_id,
                'journal_number' => 'DRAFT-'.uniqid(),
                'journal_date' => $reversalDate ?? now()->toDateString(),
                'reference_type' => $journal->reference_type,
                'reference_id' => $journal->reference_id,
                'description' => "Reversal of {$journal->journal_number}".($reason ? ": {$reason}" : ''),
                'currency_id' => $journal->currency_id,
                'exchange_rate' => $journal->exchange_rate,
                'status' => Journal::STATUS_APPROVED,
                'reversal_of_journal_id' => $journal->id,
                'reversal_reason' => $reason,
                'created_by' => Auth::id(),
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            foreach ($journal->lines as $line) {
                $reversal->lines()->create([
                    'account_id' => $line->account_id,
                    'description' => $line->description,
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                    'currency_debit' => $line->currency_credit,
                    'currency_credit' => $line->currency_debit,
                    'cost_center_id' => $line->cost_center_id,
                    'department_id' => $line->department_id,
                    'branch_id' => $line->branch_id,
                    'business_unit_id' => $line->business_unit_id,
                    'project_id' => $line->project_id,
                    'tax_id' => $line->tax_id,
                    'reference' => $line->reference,
                ]);
            }

            $reversal->calculateTotals();
            $reversal->save();

            $this->postLocked($reversal);

            $journal->update([
                'status' => Journal::STATUS_REVERSED,
                'reversal_reason' => $reason,
                'reversed_at' => now(),
                'reversed_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $this->audit->logCustom('Finance', 'Journal', $journal->id, 'REVERSE', [
                'reversal_journal_id' => $reversal->id,
                'reason' => $reason,
            ]);

            return $reversal->fresh();
        });
    }

    public function cancel(Journal $journal): Journal
    {
        if (! $journal->canCancel()) {
            throw new InvalidAccountingTransactionException('Journal cannot be cancelled');
        }

        $journal->update([
            'status' => Journal::STATUS_CANCELLED,
            'updated_by' => Auth::id(),
        ]);

        $this->audit->logCustom('Finance', 'Journal', $journal->id, 'CANCEL', []);

        return $journal->fresh();
    }

    public function update(Journal $journal, array $data): Journal
    {
        if (! $journal->isDraft()) {
            throw new InvalidAccountingTransactionException('Can only update draft journals');
        }

        $journal->update(array_merge($data, [
            'updated_by' => Auth::id(),
        ]));

        return $journal->fresh();
    }

    public function delete(Journal $journal): void
    {
        if (! $journal->isDraft() && $journal->status !== Journal::STATUS_CANCELLED) {
            throw new InvalidAccountingTransactionException('Posted journals cannot be deleted');
        }

        $journal->lines()->delete();
        $journal->delete();

        $this->audit->logDelete('Finance', 'Journal', $journal->id, $journal->toArray());
    }

    /**
     * Validate and post a journal whose row the caller has locked: assign its period and official number.
     */
    protected function postLocked(Journal $journal): void
    {
        $journalDate = Carbon::parse($journal->journal_date);
        $period = $this->periodService->validateDateForPosting($journal->company_id, $journalDate);

        $this->validate($journal);
        $this->validateBudget($journal);

        $journal->update([
            'journal_number' => $this->documentNumber->generateNumber($journal->company_id, 'JV', $period->fiscal_year_id, $journalDate),
            'fiscal_period_id' => $period->id,
            'posting_date' => now()->toDateString(),
            'status' => Journal::STATUS_POSTED,
            'posted_at' => now(),
            'posted_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
    }

    protected function ensureAccountCanReceivePosting(Journal $journal, int $accountId): void
    {
        $account = Account::withoutGlobalScope(CompanyScope::class)->findOrFail($accountId);

        if ((int) $account->company_id !== (int) $journal->company_id) {
            throw new InvalidAccountingTransactionException('Journal lines must use accounts of the journal\'s company.');
        }

        $this->ensureAccountIsPostable($account);
    }

    protected function ensureAccountIsPostable(Account $account): void
    {
        if ($account->canReceivePosting()) {
            return;
        }

        if (! $account->isActive()) {
            throw new InactiveAccountException($account);
        }

        throw new NonPostableAccountException($account);
    }

    protected function validateBudget(Journal $journal): void
    {
        if (config('finance.controls.budget_control') === 'off') {
            return;
        }

        $budgetService = app(BudgetService::class);

        foreach ($journal->lines as $line) {
            if ($line->debit > 0 && $line->account->account_type === 'EXPENSE') {
                $budgetLines = BudgetLine::where('account_id', $line->account_id)
                    ->whereHas('budget', function ($q) use ($journal) {
                        $q->where('company_id', $journal->company_id)
                            ->where('status', 'active');
                    })
                    ->get();

                foreach ($budgetLines as $budgetLine) {
                    $budgetAmount = $budgetLine->budget_amount;
                    $actualSpending = $budgetService->getActualSpending(
                        $line->account_id,
                        $budgetLine->cost_center_id,
                        $budgetLine->budget->fiscal_year_id
                    );

                    if (bccomp(bcadd((string) $actualSpending, (string) $line->debit, 4), (string) $budgetAmount, 4) === 1) {
                        throw new InvalidAccountingTransactionException(
                            "Budget exceeded for account {$line->account->account_name}. ".
                            "Budget: {$budgetAmount}, Actual: {$actualSpending}, Attempted: {$line->debit}"
                        );
                    }
                }
            }
        }
    }
}
