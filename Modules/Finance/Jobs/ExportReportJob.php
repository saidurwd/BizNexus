<?php

namespace Modules\Finance\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Finance\Services\FinancialReportService;
use Modules\Core\Models\Company;

class ExportReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $reportType,
        public array $filters,
        public int $userId,
        public int $companyId,
        public string $format = 'pdf'
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

        $filename = "{$this->reportType}_" . now()->format('Ymd_His') . "." . $this->format;
        $path = "reports/{$this->companyId}/{$filename}";

        if ($this->format === 'pdf') {
            $this->generatePdf($report, $path);
        } elseif ($this->format === 'excel') {
            $this->generateExcel($report, $path);
        }

        cache()->put("report:{$this->reportType}:" . md5(serialize($this->filters)), [
            'path' => $path,
            'filename' => $filename,
            'format' => $this->format,
            'generated_at' => now(),
        ], now()->addHours(24));
    }

    protected function generatePdf(array $report, string $path): void
    {
        // PDF generation logic using a PDF library like DomPDF or Snappy
        // For now, we'll store the data and implement PDF generation later
        cache()->put("report_pdf:" . md5($path), $report, now()->addHours(24));
    }

    protected function generateExcel(array $report, string $path): void
    {
        // Excel generation logic using a library like Maatwebsite/Excel
        // For now, we'll store the data and implement Excel generation later
        cache()->put("report_excel:" . md5($path), $report, now()->addHours(24));
    }
}
