<?php

namespace Modules\Finance\Controllers\Web;

use Modules\Finance\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Models\Tax;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;

class TaxController extends Controller
{
    public function __construct(
        CompanyContextService $companyContext,
        PermissionService $permissionService
    ) {
        parent::__construct($companyContext, $permissionService);
    }

    public function index(Request $request)
    {
        $this->checkPermission('finance.taxes.view');

        $taxes = Tax::orderBy('tax_code')->paginate(20);

        return view('finance.taxes.index', compact('taxes'));
    }

    public function create()
    {
        $this->checkPermission('finance.taxes.create');

        return view('finance.taxes.create');
    }

    public function store(Request $request)
    {
        $this->checkPermission('finance.taxes.create');

        $validated = $request->validate([
            'tax_code' => 'required|string|max:20|unique:taxes,tax_code',
            'tax_name' => 'required|string|max:100',
            'rate' => 'required|numeric|min:0|max:100',
            'tax_type' => 'required|in:VAT,WITHHOLDING_TAX,INCOME_TAX,OTHER',
            'is_inclusive' => 'nullable|boolean',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['is_inclusive'] = $validated['is_inclusive'] ?? false;

        Tax::create($validated);

        return redirect()
            ->route('finance.taxes.index')
            ->with('success', 'Tax code created successfully');
    }

    public function show(string $id)
    {
        $this->checkPermission('finance.taxes.view');

        $tax = Tax::findOrFail($id);

        return view('finance.taxes.show', compact('tax'));
    }

    public function edit(string $id)
    {
        $this->checkPermission('finance.taxes.update');

        $tax = Tax::findOrFail($id);

        return view('finance.taxes.edit', compact('tax'));
    }

    public function update(Request $request, string $id)
    {
        $this->checkPermission('finance.taxes.update');

        $tax = Tax::findOrFail($id);

        $validated = $request->validate([
            'tax_code' => 'required|string|max:20|unique:taxes,tax_code,' . $tax->id,
            'tax_name' => 'required|string|max:100',
            'rate' => 'required|numeric|min:0|max:100',
            'tax_type' => 'required|in:VAT,WITHHOLDING_TAX,INCOME_TAX,OTHER',
            'is_inclusive' => 'nullable|boolean',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['is_inclusive'] = $validated['is_inclusive'] ?? false;

        $tax->update($validated);

        return redirect()
            ->route('finance.taxes.index')
            ->with('success', 'Tax updated successfully');
    }

    public function destroy(string $id)
    {
        $this->checkPermission('finance.taxes.delete');

        $tax = Tax::findOrFail($id);
        $tax->delete();

        return redirect()
            ->route('finance.taxes.index')
            ->with('success', 'Tax deleted successfully');
    }
}
