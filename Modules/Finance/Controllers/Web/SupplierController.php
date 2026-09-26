<?php

namespace Modules\Finance\Controllers\Web;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;
use Modules\Finance\Controllers\Concerns\ManagesParties;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Models\Supplier;
use Modules\Finance\Services\PaymentService;

class SupplierController extends Controller
{
    use ManagesParties;

    public function __construct(
        CompanyContextService $companyContext,
        PermissionService $permissionService
    ) {
        parent::__construct($companyContext, $permissionService);
    }

    public function index(Request $request)
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', 'in:active,inactive']]);
        $term = isset($filters['q']) ? '%'.addcslashes($filters['q'], '%_\\').'%' : null;

        $suppliers = Supplier::with('paymentTerm')
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($term, fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', $term)->orWhere('supplier_code', 'like', $term)->orWhere('email', 'like', $term)->orWhere('tax_number', 'like', $term)))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('finance.suppliers.index', compact('suppliers', 'filters'));
    }

    public function create()
    {
        return view('finance.suppliers.create', ['party' => null, 'isCustomer' => false, ...$this->partyFormData('LIABILITY')]);
    }

    public function store(Request $request)
    {
        $supplier = Supplier::create([
            ...$this->validatedParty($request, 'suppliers', 'supplier_code', 'payable_account_id'),
            'company_id' => $this->getActiveCompanyId(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('finance.suppliers.show', $supplier->id)->with('success', __('Supplier created.'));
    }

    public function show(int $id, PaymentService $payments)
    {
        $supplier = Supplier::with(['currency', 'paymentTerm'])->findOrFail($id);

        return view('finance.parties.show', [
            'party' => $supplier,
            'isCustomer' => false,
            'openBalance' => $payments->getAPAging((int) $supplier->company_id, $supplier->id)['total'],
            'availableCredit' => null,
        ]);
    }

    public function edit(int $id)
    {
        return view('finance.suppliers.edit', ['party' => Supplier::findOrFail($id), 'isCustomer' => false, ...$this->partyFormData('LIABILITY')]);
    }

    public function update(Request $request, int $id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->update([
            ...$this->validatedParty($request, 'suppliers', 'supplier_code', 'payable_account_id', $supplier->id),
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('finance.suppliers.show', $supplier->id)->with('success', __('Supplier updated.'));
    }

    public function destroy(int $id)
    {

        try {
            Supplier::findOrFail($id)->delete();
        } catch (QueryException) {
            return back()->with('error', __('This supplier has invoices or payments and cannot be deleted. Make it inactive instead.'));
        }

        return redirect()->route('finance.suppliers.index')->with('success', __('Supplier deleted.'));
    }
}
