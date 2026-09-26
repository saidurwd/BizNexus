<?php

namespace Modules\Finance\Controllers\Web;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Services\TaxReturnService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaxReturnController extends Controller
{
    public function index(Request $request, TaxReturnService $taxReturns)
    {
        [$from, $to] = $this->period($request);
        $summary = $taxReturns->summarise($this->companyContext->getActiveCompany(), $from, $to);

        if ($request->query('format') === 'csv') {
            return $this->csv($summary, $from, $to);
        }

        return view('finance.reports.tax-return', ['summary' => $summary, 'from' => $from->toDateString(), 'to' => $to->toDateString()]);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function period(Request $request): array
    {
        $request->validate(['from' => 'nullable|date', 'to' => 'nullable|date|after_or_equal:from']);

        return [
            Carbon::parse($request->query('from', now()->subMonthNoOverflow()->startOfMonth()->toDateString())),
            Carbon::parse($request->query('to', now()->subMonthNoOverflow()->endOfMonth()->toDateString())),
        ];
    }

    protected function csv(array $summary, Carbon $from, Carbon $to): StreamedResponse
    {
        return response()->streamDownload(function () use ($summary) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Section', 'Tax code', 'Tax name', 'Taxable amount', 'Tax amount', 'Currency']);

            foreach ($summary['boxes'] as $box => $rows) {
                foreach ($rows as $row) {
                    fputcsv($handle, [$box, $row['tax_code'], $row['tax_name'], $row['taxable']->amount, $row['tax']->amount, $summary['currency']]);
                }
            }

            fputcsv($handle, ['net_payable', '', '', '', $summary['net_payable']->amount, $summary['currency']]);
            fclose($handle);
        }, "tax-return-{$from->toDateString()}-{$to->toDateString()}.csv", ['Content-Type' => 'text/csv']);
    }
}
