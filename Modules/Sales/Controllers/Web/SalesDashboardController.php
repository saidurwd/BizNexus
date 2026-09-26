<?php

namespace Modules\Sales\Controllers\Web;

use Illuminate\View\View;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Controllers\Controller;
use Modules\Sales\Services\SalesDashboardService;

class SalesDashboardController extends Controller
{
    public function __invoke(SalesDashboardService $dashboard): View
    {
        $today = app(CompanyContextService::class)->today();
        $yearAgo = $today->subMonths(11)->startOfMonth();

        return view('sales.dashboard', [
            'summary' => $dashboard->summary($this->getActiveCompanyId(), $today),
            'months' => $dashboard->monthlySales($yearAgo, $today),
            'topCustomers' => $dashboard->topCustomers($yearAgo, $today),
            'topProducts' => $dashboard->topProducts($yearAgo, $today),
            'dueOrders' => $dashboard->dueForDelivery($today->addDays(7)),
            'today' => $today,
            'currency' => app(CompanyContextService::class)->getBaseCurrency()?->code,
        ]);
    }
}
