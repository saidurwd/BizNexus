<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Services\FinanceDashboardService;
use Modules\Workflow\Services\WorkflowService;

/**
 * The home dashboard: finance figures for users allowed to see them, and each user's pending work.
 */
class HomeController extends Controller
{
    public function __invoke(
        Request $request,
        CompanyContextService $companyContext,
        FinanceDashboardService $financeDashboard,
        WorkflowService $workflowService,
    ): View {
        $company = $companyContext->getActiveCompany();
        $today = $companyContext->today();
        $user = $request->user();
        $showsFinance = $company && $user->can('finance.dashboard.view');

        return view('dashboard', [
            'company' => $company,
            'today' => $today,
            'currency' => $company?->baseCurrency?->code,
            'summary' => $showsFinance ? $financeDashboard->summary($company->id, $today) : null,
            'performance' => $showsFinance ? $financeDashboard->monthlyPerformance($company->id, $today) : null,
            'attentionItems' => collect($company ? $financeDashboard->attentionItems($today) : [])
                ->filter(fn (array $item) => $user->can($item['permission']))
                ->values(),
            'overdueCustomers' => $showsFinance && $user->can('finance.customer-invoices.view')
                ? $financeDashboard->topOverdueCustomers($today)
                : collect(),
            'myApprovalCount' => $company && $user->can('core.workflow.view')
                ? $workflowService->getPendingApprovals($user->id)->flatten()->count()
                : 0,
        ]);
    }
}
