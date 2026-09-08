<?php

namespace Modules\Finance\Controllers\Web;

use Modules\Finance\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Models\Supplier;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;

class SupplierController extends Controller
{
    public function __construct(
        CompanyContextService $companyContext,
        PermissionService $permissionService
    ) {
        parent::__construct($companyContext, $permissionService);
    }

    public function index(Request $request)
    {
        $this->checkPermission('finance.suppliers.view');

        $suppliers = Supplier::when($request->get('status'), fn($q, $status) => $q->where('status', $status))
            ->orderBy('name')
            ->get();

        return view('finance.suppliers.index', [
            'suppliers' => $suppliers,
        ]);
    }

    public function create()
    {
        $this->checkPermission('finance.suppliers.create');

        return view('finance.suppliers.create');
    }

    public function store(Request $request)
    {
        $this->checkPermission('finance.suppliers.create');

        $validated = $request->validate([            'supplier_code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'tax_number' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,inactive',
        ]);

        $validated['company_id'] = $this->getActiveCompanyId();

        Supplier::create($validated);

        return redirect()->route('finance.suppliers.index')->with('success', 'Supplier created successfully.');
    }

    public function show(int $id)
    {
        $this->checkPermission('finance.suppliers.view');

        $supplier = Supplier::with(['invoices', 'payments'])
            ->findOrFail($id);

        return view('finance.suppliers.show', [
            'supplier' => $supplier,
        ]);
    }

    public function edit(int $id)
    {
        $this->checkPermission('finance.suppliers.update');

        $supplier = Supplier::findOrFail($id);

        return view('finance.suppliers.edit', [
            'supplier' => $supplier,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $this->checkPermission('finance.suppliers.update');

        $supplier = Supplier::findOrFail($id);

        $validated = $request->validate([            'supplier_code' => 'required|string|max:50|unique:suppliers,supplier_code,' . $supplier->id,
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'tax_number' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,inactive',
        ]);

        $validated['company_id'] = $this->getActiveCompanyId();

        $supplier->update($validated);

        return redirect()->route('finance.suppliers.index')->with('success', 'Supplier updated successfully.');
    }

    public function destroy(int $id)
    {
        $this->checkPermission('finance.suppliers.delete');

        $supplier = Supplier::findOrFail($id);
        $supplier->delete();

        return redirect()->route('finance.suppliers.index')->with('success', 'Supplier deleted successfully.');
    }
}
