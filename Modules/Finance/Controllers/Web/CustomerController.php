<?php

namespace Modules\Finance\Controllers\Web;

use Modules\Finance\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Models\Customer;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;

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
        $this->checkPermission('finance.customers.view');

        $customers = Customer::when($request->get('status'), fn($q, $status) => $q->where('status', $status))
            ->orderBy('name')
            ->get();

        return view('finance.customers.index', [
            'customers' => $customers,
        ]);
    }

    public function create()
    {
        $this->checkPermission('finance.customers.create');

        return view('finance.customers.create');
    }

    public function store(Request $request)
    {
        $this->checkPermission('finance.customers.create');

        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'customer_code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'tax_number' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,inactive',
        ]);

        Customer::create($validated);

        return redirect()->route('finance.customers.index')->with('success', 'Customer created successfully.');
    }

    public function show(int $id)
    {
        $this->checkPermission('finance.customers.view');

        $customer = Customer::with(['invoices', 'receipts'])
            ->findOrFail($id);

        return view('finance.customers.show', [
            'customer' => $customer,
        ]);
    }
}
