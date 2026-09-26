<?php

namespace Modules\Finance\Controllers\Web;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Services\FinancialReportService;
use Modules\Finance\Services\LedgerService;
use Modules\Finance\Services\PaymentService;
use Modules\Finance\Services\ReceiptService;
use Modules\Finance\Support\SpreadsheetExport;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Excel exports of the financial reports, taking the same filters as the report screens.
 */
class ReportExportController extends Controller
{
    public const REPORTS = ['trial-balance', 'profit-loss', 'balance-sheet', 'cash-flow', 'ar-aging', 'ap-aging'];

    public function __invoke(Request $request, string $report): StreamedResponse
    {
        $filters = $request->validate([
            'date' => ['nullable', 'date'],
            'as_of_date' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
        $companyId = (int) $this->getActiveCompanyId();
        $company = app(CompanyContextService::class)->getActiveCompany();
        $date = fn (string $key) => isset($filters[$key]) ? Carbon::parse($filters[$key]) : null;

        [$title, $headings, $rows] = match ($report) {
            'trial-balance' => $this->trialBalance(app(LedgerService::class)->getTrialBalance($companyId, null, $date('date'))),
            'profit-loss' => $this->profitAndLoss(app(FinancialReportService::class)->getProfitAndLoss($companyId, null, $date('start_date'), $date('end_date'))),
            'balance-sheet' => $this->balanceSheet(app(FinancialReportService::class)->getBalanceSheet($companyId, $date('as_of_date'))),
            'cash-flow' => $this->cashFlow(app(FinancialReportService::class)->getCashFlow($companyId, $date('start_date'), $date('end_date'))),
            'ar-aging' => $this->aging(__('Receivables ageing'), app(ReceiptService::class)->getARAging($companyId, null, $this->asOf($filters))),
            'ap-aging' => $this->aging(__('Payables ageing'), app(PaymentService::class)->getAPAging($companyId, null, $this->asOf($filters))),
            default => abort(404),
        };

        return SpreadsheetExport::download(
            $report.'-'.now()->format('Ymd'),
            [$title, $company?->legal_name ?: $company?->name, __('Currency: :code', ['code' => $company?->baseCurrency?->code])],
            $headings,
            $rows,
        );
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array{0: string, 1: list<string>, 2: iterable<int, list<mixed>>}
     */
    protected function trialBalance(array $report): array
    {
        $rows = collect($report['accounts'])->map(fn (array $account) => [
            (string) $account['account_code'], $account['account_name'], $account['account_type'],
            (float) $account['closing_debit'], (float) $account['closing_credit'],
        ])->push(['', __('Total'), '', (float) $report['total_closing_debit'], (float) $report['total_closing_credit']]);

        return [__('Trial balance as of :date', ['date' => $report['date'] ?? now()->toDateString()]), [__('Code'), __('Account'), __('Type'), __('Debit'), __('Credit')], $rows];
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array{0: string, 1: list<string>, 2: iterable<int, list<mixed>>}
     */
    protected function profitAndLoss(array $report): array
    {
        $section = fn (string $label, array $group) => collect([[$label, '', null]])
            ->concat(collect($group['accounts'])->map(fn (array $account) => [(string) $account['account_code'], $account['account_name'], (float) $account['amount']]))
            ->push(['', __('Total :section', ['section' => mb_strtolower($label)]), (float) $group['total']]);

        $rows = $section(__('Revenue'), $report['revenue'])
            ->concat($section(__('Expenses'), $report['expenses']))
            ->push(['', __('Net profit'), (float) $report['net_profit']]);

        return [__('Profit and loss'), [__('Code'), __('Account'), __('Amount')], $rows];
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array{0: string, 1: list<string>, 2: iterable<int, list<mixed>>}
     */
    protected function balanceSheet(array $report): array
    {
        $section = fn (string $label, array $group) => collect([[$label, '', null]])
            ->concat(collect($group['accounts'])->map(fn (array $account) => [(string) $account['account_code'], $account['account_name'], (float) $account['amount']]));

        $rows = $section(__('Assets'), $report['assets'])
            ->push(['', __('Total assets'), (float) $report['assets']['total']])
            ->concat($section(__('Liabilities'), $report['liabilities']))
            ->push(['', __('Total liabilities'), (float) $report['liabilities']['total']])
            ->concat($section(__('Equity'), $report['equity']))
            ->push(['', __('Profit for the current year'), (float) $report['equity']['current_year_profit']])
            ->push(['', __('Total equity'), (float) $report['equity']['total']])
            ->push(['', __('Total liabilities and equity'), (float) $report['total_liabilities_equity']]);

        return [__('Statement of financial position as of :date', ['date' => $report['as_of_date']]), [__('Code'), __('Account'), __('Amount')], $rows];
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array{0: string, 1: list<string>, 2: iterable<int, list<mixed>>}
     */
    protected function cashFlow(array $report): array
    {
        $rows = collect();

        foreach (['operating' => __('Operating activities'), 'investing' => __('Investing activities'), 'financing' => __('Financing activities')] as $key => $label) {
            $rows->push([$label, null]);
            $rows = $rows->concat(collect($report[$key.'_activities'])->map(fn (array $item) => [$item['description'], (float) $item['amount']]));
            $rows->push([__('Net cash from :activity', ['activity' => mb_strtolower($label)]), (float) $report[$key.'_total']]);
        }

        $rows = $rows->concat([
            [__('Effect of exchange rate changes on cash'), (float) $report['fx_effect']],
            [__('Net change in cash and cash equivalents'), (float) $report['net_change']],
            [__('Cash and cash equivalents at the start of the period'), (float) $report['opening_cash']],
            [__('Cash and cash equivalents at the end of the period'), (float) $report['closing_cash']],
        ]);

        return [__('Statement of cash flows :from to :to', ['from' => $report['start_date'], 'to' => $report['end_date']]), [__('Line'), __('Amount')], $rows];
    }

    /**
     * @param  array<string, mixed>  $aging
     * @return array{0: string, 1: list<string>, 2: iterable<int, list<mixed>>}
     */
    protected function aging(string $title, array $aging): array
    {
        $rows = collect($aging['parties'])->map(fn (array $party) => [
            $party['party_name'], (float) $party['current'], (float) $party['days_1_30'], (float) $party['days_31_60'],
            (float) $party['days_61_90'], (float) $party['over_90_days'], (float) $party['total'],
        ])->push([__('Total'), (float) $aging['current'], (float) $aging['days_1_30'], (float) $aging['days_31_60'], (float) $aging['days_61_90'], (float) bcadd($aging['days_91_180'], $aging['days_180_plus'], 4), (float) $aging['total']]);

        return [$title.' '.__('as of :date', ['date' => $aging['as_of_date']]), [__('Name'), __('Not yet due'), __('1–30 days'), __('31–60 days'), __('61–90 days'), __('Over 90 days'), __('Total')], $rows];
    }

    /**
     * @param  array<string, string>  $filters
     */
    protected function asOf(array $filters): CarbonImmutable
    {
        return isset($filters['as_of_date']) ? CarbonImmutable::parse($filters['as_of_date']) : app(CompanyContextService::class)->today();
    }
}
