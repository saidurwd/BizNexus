<?php

namespace Modules\Finance\Controllers\Web;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Models\Supplier;
use Modules\Finance\Services\PartyStatementService;

class SupplierStatementController extends Controller
{
    public function __construct(
        CompanyContextService $companyContext,
        PermissionService $permissionService
    ) {
        parent::__construct($companyContext, $permissionService);
    }

    public function index(Request $request)
    {

        $suppliers = Supplier::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'supplier_code', 'name', 'email', 'phone']);

        return view('finance.supplier-statements.index', compact('suppliers'));
    }

    public function show(Request $request, int $id, PartyStatementService $statements)
    {
        $filters = $request->validate([
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);
        $supplier = Supplier::findOrFail($id);
        $startDate = isset($filters['start_date']) ? Carbon::parse($filters['start_date']) : null;
        $endDate = isset($filters['end_date']) ? Carbon::parse($filters['end_date']) : null;

        return view('finance.supplier-statements.show', [
            'supplier' => $supplier,
            'statement' => $statements->forSupplier($supplier, $startDate, $endDate),
            'startDate' => $startDate?->format('Y-m-d'),
            'endDate' => $endDate?->format('Y-m-d'),
        ]);
    }
}
