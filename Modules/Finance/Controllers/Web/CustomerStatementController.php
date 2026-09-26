<?php

namespace Modules\Finance\Controllers\Web;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Models\Customer;
use Modules\Finance\Services\PartyStatementService;

class CustomerStatementController extends Controller
{
    public function __construct(
        CompanyContextService $companyContext,
        PermissionService $permissionService
    ) {
        parent::__construct($companyContext, $permissionService);
    }

    public function index(Request $request)
    {

        $customers = Customer::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'customer_code', 'name', 'email', 'phone']);

        return view('finance.customer-statements.index', compact('customers'));
    }

    public function show(Request $request, int $id, PartyStatementService $statements)
    {
        $filters = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
        $customer = Customer::findOrFail($id);
        $startDate = isset($filters['start_date']) ? Carbon::parse($filters['start_date']) : null;
        $endDate = isset($filters['end_date']) ? Carbon::parse($filters['end_date']) : null;

        return view('finance.customer-statements.show', [
            'customer' => $customer,
            'statement' => $statements->forCustomer($customer, $startDate, $endDate),
            'startDate' => $startDate?->format('Y-m-d'),
            'endDate' => $endDate?->format('Y-m-d'),
        ]);
    }
}
