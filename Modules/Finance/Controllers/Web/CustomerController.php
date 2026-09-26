<?php

namespace Modules\Finance\Controllers\Web;

use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Finance\Controllers\Concerns\ManagesParties;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Models\Customer;
use Modules\Finance\Services\ReceiptService;

class CustomerController extends Controller
{
    use ManagesParties;

    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', 'in:active,inactive']]);
        $term = isset($filters['q']) ? '%'.addcslashes($filters['q'], '%_\\').'%' : null;

        $customers = Customer::with('paymentTerm')
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($term, fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', $term)->orWhere('customer_code', 'like', $term)->orWhere('email', 'like', $term)->orWhere('tax_number', 'like', $term)))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('finance.customers.index', compact('customers', 'filters'));
    }

    public function create(): View
    {
        return view('finance.customers.create', ['party' => null, 'isCustomer' => true, ...$this->partyFormData('ASSET')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $customer = Customer::create([
            ...$this->validatedParty($request, 'customers', 'customer_code', 'receivable_account_id'),
            'company_id' => $this->getActiveCompanyId(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('finance.customers.show', $customer->id)->with('success', __('Customer created.'));
    }

    public function show(int $id, ReceiptService $receipts): View
    {
        $customer = Customer::with(['invoices', 'receipts', 'currency', 'paymentTerm'])->findOrFail($id);
        $aging = $receipts->getARAging((int) $customer->company_id, $customer->id);

        return view('finance.parties.show', [
            'party' => $customer,
            'isCustomer' => true,
            'openBalance' => $aging['total'],
            'availableCredit' => $customer->credit_limit !== null ? bcsub((string) $customer->credit_limit, $aging['total'], 4) : null,
        ]);
    }

    public function edit(int $id): View
    {
        return view('finance.customers.edit', ['party' => Customer::findOrFail($id), 'isCustomer' => true, ...$this->partyFormData('ASSET')]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $customer = Customer::findOrFail($id);
        $customer->update([
            ...$this->validatedParty($request, 'customers', 'customer_code', 'receivable_account_id', $customer->id),
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('finance.customers.show', $customer->id)->with('success', __('Customer updated.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            Customer::findOrFail($id)->delete();
        } catch (QueryException) {
            return back()->with('error', __('This customer has invoices or receipts and cannot be deleted. Make it inactive instead.'));
        }

        return redirect()->route('finance.customers.index')->with('success', __('Customer deleted.'));
    }
}
