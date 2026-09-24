<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Modules\Core\Models\Company;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Services\FxRevaluationService;
use Throwable;

#[Signature('finance:revalue {date? : Revaluation date, defaults to the end of last month} {--company=* : Company codes; all active companies when omitted}')]
#[Description('Revalue foreign-currency monetary balances at the closing rate for each company')]
class RunFxRevaluation extends Command
{
    public function handle(FxRevaluationService $revaluations, CompanyContextService $companyContext): int
    {
        $date = $this->argument('date') ? Carbon::parse($this->argument('date')) : now()->subMonthNoOverflow()->endOfMonth();
        $failed = false;

        $companies = Company::where('status', 'active')
            ->when($this->option('company'), fn ($query, array $codes) => $query->whereIn('code', $codes))
            ->get();

        foreach ($companies as $company) {
            try {
                $revaluation = $companyContext->runAs($company->id, fn () => $revaluations->revalue($company, $date));
                $this->info("{$company->code}: net unrealised result {$revaluation->net_gain_loss}");
            } catch (Throwable $exception) {
                $failed = true;
                $this->error("{$company->code}: {$exception->getMessage()}");
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
