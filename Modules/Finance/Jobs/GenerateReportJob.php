<?php

namespace Modules\Finance\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Finance\Services\FinancialReportService;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $reportType,
        public array $filters,
        public int $userId,
        public ?int $companyId = null,
        public ?string $format = 'pdf'
    ) {}

    public function handle(FinancialReportService $reportService): void
    {
        $report = match ($this->reportType) {
            'trial_balance' => $reportService->getTrialBalance($this->companyId, $this->filters),
            'general_ledger' => $reportService->getGeneralLedger($this->companyId, $this->filters),
            'profit_loss' => $reportService->getProfitAndLoss($this->companyId, $this->filters),
            'balance_sheet' => $reportService->getBalanceSheet($this->companyId, $this->filters),
            'cash_flow' => $reportService->getCashFlow($this->companyId, $this->filters),
            default => throw new \InvalidArgumentException("Unknown report type: {$this->reportType}"),
        };

        // Store report in cache or filesystem for download
        cache()->put("report:{$this->reportType}:" . md5(serialize($this->filters)), $report, now()->addHours(24));
    }
}
