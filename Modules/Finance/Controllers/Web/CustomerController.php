<?php

namespace Modules\Finance\Controllers\Web;

use Illuminate\Http\Request;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Models\Customer;

class CustomerController extends Controller
{
    public function __construct(
        CompanyContextService $companyContext,
        PermissionService $permissionService
    ) {
        parent::__construct($companyContext, $permissionService);
    }

    public function index(Request $request)
    {

        $customers = Customer::when($request->get('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderBy('name')
            ->get();

        return view('finance.customers.index', [
            'customers' => $customers,
        ]);
    }

    public function create()
    {

        return view('finance.customers.create');
    }

    public function store(Request $request)
    {

        $validated = $request->validate(['customer_code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'tax_number' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,inactive',
        ]);

        $validated['company_id'] = $this->getActiveCompanyId();

        Customer::create($validated);

        return redirect()->route('finance.customers.index')->with('success', 'Customer created successfully.');
    }

    public function show(int $id)
    {

        $customer = Customer::with(['invoices', 'receipts'])
            ->findOrFail($id);

        return view('finance.customers.show', [
            'customer' => $customer,
        ]);
    }
}
