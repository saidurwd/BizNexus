<?php

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Modules\Assets\Models\AssetDepreciationRun;
use Modules\Assets\Models\FixedAsset;
use Modules\Assets\Services\DepreciationService;
use Modules\Core\Models\Company;
use Modules\Core\Services\CompanyContextService;
use Throwable;

#[Signature('assets:depreciate {month? : Month to depreciate up to (YYYY-MM), defaults to last month} {--company=* : Company codes; all active companies when omitted}')]
#[Description('Post monthly depreciation of fixed assets for each company')]
class RunAssetDepreciation extends Command
{
    public function handle(DepreciationService $depreciation, CompanyContextService $companyContext): int
    {
        $failed = false;

        $companies = Company::where('status', 'active')
            ->when($this->option('company'), fn ($query, array $codes) => $query->whereIn('code', $codes))
            ->get();

        foreach ($companies as $company) {
            try {
                $companyContext->runAs($company->id, function () use ($company, $depreciation, $companyContext) {
                    $month = $this->argument('month')
                        ? CarbonImmutable::createFromFormat('Y-m-d', $this->argument('month').'-01')
                        : $companyContext->today()->startOfMonth()->subDay();

                    if (FixedAsset::where('status', FixedAsset::STATUS_ACTIVE)->doesntExist()
                        || AssetDepreciationRun::where('status', AssetDepreciationRun::STATUS_POSTED)->whereDate('period_end', $month->endOfMonth()->toDateString())->exists()) {
                        $this->line("{$company->code}: nothing to depreciate");

                        return;
                    }

                    $run = $depreciation->run($month);
                    $this->info("{$company->code}: depreciation {$run->total_amount} on {$run->asset_count} assets");
                });
            } catch (Throwable $exception) {
                $failed = true;
                $this->error("{$company->code}: {$exception->getMessage()}");
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
