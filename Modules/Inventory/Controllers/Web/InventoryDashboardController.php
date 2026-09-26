<?php

namespace Modules\Inventory\Controllers\Web;

use Illuminate\View\View;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Controllers\Controller;
use Modules\Inventory\Services\InventoryDashboardService;

class InventoryDashboardController extends Controller
{
    public function __invoke(InventoryDashboardService $dashboard): View
    {
        $today = app(CompanyContextService::class)->today();

        return view('inventory.dashboard', [
            'summary' => $dashboard->summary($today),
            'ageing' => $dashboard->ageing($today),
            'slowMovers' => $dashboard->slowMovers($today),
            'topByValue' => $dashboard->topByValue(),
            'today' => $today,
            'currency' => app(CompanyContextService::class)->getBaseCurrency()?->code,
        ]);
    }
}
