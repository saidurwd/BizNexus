<?php

namespace Modules\Assets\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Assets\Models\AssetDepreciationRun;
use Modules\Assets\Models\AssetTransaction;
use Modules\Assets\Models\FixedAsset;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Services\AuditService;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Support\Money;
use Modules\Finance\Models\Journal;
use Modules\Finance\Services\JournalService;

/**
 * Monthly depreciation. A run charges every asset in use for each month from the one after its last charge
 * up to the run's month end (so a missed month is caught up), and posts one journal: depreciation expense
 * by branch and department against accumulated depreciation. The latest run can be reversed.
 */
class DepreciationService
{
    public function __construct(
        protected JournalService $journalService,
        protected AuditService $audit,
        protected CompanyContextService $companyContext,
    ) {}

    /**
     * What a run to this month end would charge, per asset, without posting anything.
     *
     * @return Collection<int, array{asset: FixedAsset, months: int, amount: string}>
     */
    public function preview(CarbonImmutable $periodEnd): Collection
    {
        return $this->dueAssets($periodEnd)
            ->map(function (FixedAsset $asset) use ($periodEnd) {
                $charge = $this->chargeThrough($asset->replicate()->forceFill(['id' => $asset->id]), $periodEnd, persist: false);

                return ['asset' => $asset, 'months' => $charge['months'], 'amount' => $charge['amount']];
            })
            ->filter(fn (array $row) => bccomp($row['amount'], '0', 4) > 0)
            ->values();
    }

    public function run(CarbonImmutable $periodEnd): AssetDepreciationRun
    {
        $periodEnd = $periodEnd->endOfMonth()->startOfDay();

        return DB::transaction(function () use ($periodEnd) {
            if (AssetDepreciationRun::where('status', AssetDepreciationRun::STATUS_POSTED)->whereDate('period_end', $periodEnd->toDateString())->lockForUpdate()->exists()) {
                throw new InvalidAccountingTransactionException(__('Depreciation for :month has already been posted.', ['month' => $periodEnd->translatedFormat('F Y')]));
            }

            $run = AssetDepreciationRun::create([
                'company_id' => $this->companyContext->getActiveCompanyId(),
                'period_end' => $periodEnd->toDateString(),
                'status' => AssetDepreciationRun::STATUS_POSTED,
                'created_by' => Auth::id(),
            ]);

            $functional = $this->functionalCurrency();
            $total = Money::zero($functional);
            $groups = [];
            $charged = [];

            foreach ($this->dueAssets($periodEnd, lock: true) as $asset) {
                $previousUntil = $asset->depreciated_until?->toDateString();
                $previousStatus = $asset->status;
                $charge = $this->chargeThrough($asset, $periodEnd);

                if (bccomp($charge['amount'], '0', 4) <= 0) {
                    continue;
                }

                $amount = Money::of($charge['amount'], $functional);
                $total = $total->plus($amount);
                $category = $asset->category;
                $key = implode(':', [$category->depreciation_expense_account_id, $category->accumulated_depreciation_account_id, $asset->branch_id, $asset->department_id]);
                $groups[$key] ??= ['category' => $category, 'branch_id' => $asset->branch_id, 'department_id' => $asset->department_id, 'amount' => Money::zero($functional)];
                $groups[$key]['amount'] = $groups[$key]['amount']->plus($amount);

                $charged[] = AssetTransaction::create([
                    'company_id' => $asset->company_id,
                    'fixed_asset_id' => $asset->id,
                    'type' => AssetTransaction::TYPE_DEPRECIATION,
                    'transaction_date' => $periodEnd->toDateString(),
                    'amount' => $amount->amount,
                    'depreciation_run_id' => $run->id,
                    'details' => ['months' => $charge['months'], 'previous_until' => $previousUntil, 'previous_status' => $previousStatus],
                    'created_by' => Auth::id(),
                ]);
            }

            if ($charged === []) {
                throw new InvalidAccountingTransactionException(__('No asset has depreciation to charge up to :date.', ['date' => $periodEnd->toDateString()]));
            }

            $description = 'Depreciation for '.$periodEnd->format('F Y');
            $lines = [];

            foreach ($groups as $group) {
                $lines[] = ['account_id' => $group['category']->depreciation_expense_account_id, 'description' => "{$description} — {$group['category']->name}", 'debit' => $group['amount']->amount, 'credit' => 0, 'branch_id' => $group['branch_id'], 'department_id' => $group['department_id']];
                $lines[] = ['account_id' => $group['category']->accumulated_depreciation_account_id, 'description' => "{$description} — {$group['category']->name}", 'debit' => 0, 'credit' => $group['amount']->amount];
            }

            $journal = $this->journalService->postFromSource([
                'company_id' => $run->company_id,
                'journal_date' => $periodEnd->toDateString(),
                'reference_type' => 'asset_depreciation_run',
                'reference_id' => $run->id,
                'description' => $description,
                'lines' => $lines,
            ]);

            $run->update(['journal_id' => $journal->id, 'total_amount' => $total->amount, 'asset_count' => count($charged)]);
            AssetTransaction::whereKey(collect($charged)->pluck('id'))->update(['journal_id' => $journal->id]);
            $this->audit->logCustom('Assets', 'AssetDepreciationRun', $run->id, 'POST', ['period_end' => $periodEnd->toDateString(), 'total' => $total->amount, 'journal_id' => $journal->id]);

            return $run->fresh();
        });
    }

    /**
     * Reverse the latest run: its journal is reversed and the assets go back to where they were before it.
     */
    public function reverse(AssetDepreciationRun $run, ?string $reason = null): AssetDepreciationRun
    {
        return DB::transaction(function () use ($run, $reason) {
            $run = AssetDepreciationRun::whereKey($run->id)->lockForUpdate()->firstOrFail();

            if ($run->status !== AssetDepreciationRun::STATUS_POSTED) {
                throw new InvalidAccountingTransactionException(__('This run has already been reversed.'));
            }

            if (AssetDepreciationRun::where('status', AssetDepreciationRun::STATUS_POSTED)->whereDate('period_end', '>', $run->period_end->toDateString())->exists()) {
                throw new InvalidAccountingTransactionException(__('Reverse the later depreciation runs first.'));
            }

            $transactions = $run->transactions()->with('asset')->get();
            $laterActivity = AssetTransaction::whereIn('fixed_asset_id', $transactions->pluck('fixed_asset_id'))
                ->where('id', '>', $transactions->max('id'))
                ->whereIn('type', [AssetTransaction::TYPE_DEPRECIATION, AssetTransaction::TYPE_IMPAIRMENT, AssetTransaction::TYPE_DISPOSAL])
                ->exists();

            if ($laterActivity) {
                throw new InvalidAccountingTransactionException(__('Some of these assets were impaired or disposed of after this run, so it can no longer be reversed.'));
            }

            $reversal = $this->journalService->reverse(Journal::findOrFail($run->journal_id), $reason ?? 'Depreciation run reversed', $run->period_end->toDateString());

            foreach ($transactions as $transaction) {
                $asset = $transaction->asset;
                $asset->update([
                    'accumulated_depreciation' => bcsub((string) $asset->accumulated_depreciation, (string) $transaction->amount, 4),
                    'months_depreciated' => max(0, $asset->months_depreciated - (int) ($transaction->details['months'] ?? 0)),
                    'depreciated_until' => $transaction->details['previous_until'] ?? null,
                    'status' => $transaction->details['previous_status'] ?? FixedAsset::STATUS_ACTIVE,
                ]);
                $transaction->delete();
            }

            $run->update(['status' => AssetDepreciationRun::STATUS_REVERSED, 'reversal_journal_id' => $reversal->id]);
            $this->audit->logCustom('Assets', 'AssetDepreciationRun', $run->id, 'REVERSE', ['reason' => $reason, 'journal_id' => $reversal->id]);

            return $run->fresh();
        });
    }

    /**
     * Charge each month from the asset's next month up to $until (a month end), rounding each month to the
     * currency. Updates the asset (unless previewing) and returns the total.
     *
     * @return array{amount: string, months: int, until: string|null}
     */
    public function chargeThrough(FixedAsset $asset, CarbonImmutable $until, bool $persist = true): array
    {
        $functional = $this->functionalCurrency();
        $total = Money::zero($functional);
        $months = 0;
        $until = $until->endOfMonth()->startOfDay();

        while ($asset->status === FixedAsset::STATUS_ACTIVE && $asset->nextDepreciationMonth()->lte($until) && bccomp($asset->depreciableAmount(), '0', 4) > 0) {
            $month = $asset->nextDepreciationMonth();
            $charge = Money::of($asset->nextMonthDepreciation(), $functional);

            $asset->forceFill([
                'accumulated_depreciation' => bcadd((string) $asset->accumulated_depreciation, $charge->amount, 4),
                'months_depreciated' => $asset->months_depreciated + 1,
                'depreciated_until' => $month->toDateString(),
            ]);

            if (bccomp($asset->depreciableAmount(), '0', 4) <= 0) {
                $asset->status = FixedAsset::STATUS_FULLY_DEPRECIATED;
            }

            $total = $total->plus($charge);
            $months++;
        }

        if ($persist && $months > 0) {
            $asset->save();
        }

        return ['amount' => $total->amount, 'months' => $months, 'until' => $asset->depreciated_until?->toDateString()];
    }

    /**
     * @return Collection<int, FixedAsset>
     */
    protected function dueAssets(CarbonImmutable $periodEnd, bool $lock = false): Collection
    {
        return FixedAsset::with('category')
            ->where('status', FixedAsset::STATUS_ACTIVE)
            ->whereDate('in_service_date', '<=', $periodEnd->endOfMonth()->toDateString())
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->orderBy('asset_number')
            ->get();
    }

    protected function functionalCurrency(): string
    {
        return $this->companyContext->getBaseCurrency()?->code ?? 'XXX';
    }
}
