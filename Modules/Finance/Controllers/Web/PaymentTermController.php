<?php

namespace Modules\Finance\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Models\Customer;
use Modules\Finance\Models\PaymentTerm;
use Modules\Finance\Models\Supplier;

class PaymentTermController extends Controller
{
    public function index(): View
    {
        $paymentTerms = PaymentTerm::orderBy('due_days')->orderBy('code')->get();
        $usage = [
            'customers' => Customer::whereNotNull('payment_term_id')->selectRaw('payment_term_id, COUNT(*) AS party_count')->groupBy('payment_term_id')->pluck('party_count', 'payment_term_id'),
            'suppliers' => Supplier::whereNotNull('payment_term_id')->selectRaw('payment_term_id, COUNT(*) AS party_count')->groupBy('payment_term_id')->pluck('party_count', 'payment_term_id'),
        ];

        return view('finance.payment-terms.index', compact('paymentTerms', 'usage'));
    }

    public function store(Request $request): RedirectResponse
    {
        PaymentTerm::create([...$this->validated($request), 'company_id' => $this->getActiveCompanyId()]);

        return back()->with('success', __('Payment term added.'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $paymentTerm = PaymentTerm::findOrFail($id);
        $paymentTerm->update($this->validated($request, $paymentTerm->id));

        return back()->with('success', __('Payment term updated.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $paymentTerm = PaymentTerm::findOrFail($id);

        if (Customer::where('payment_term_id', $id)->exists() || Supplier::where('payment_term_id', $id)->exists()) {
            return back()->with('error', __('This payment term is used by customers or suppliers. Make it inactive instead.'));
        }

        $paymentTerm->delete();

        return back()->with('success', __('Payment term deleted.'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('payment_terms', 'code')->where('company_id', $this->getActiveCompanyId())->ignore($ignoreId)],
            'name' => ['required', 'string', 'max:255'],
            'due_days' => ['required', 'integer', 'min:0', 'max:3650'],
            'due_basis' => ['required', Rule::in([PaymentTerm::BASIS_INVOICE_DATE, PaymentTerm::BASIS_END_OF_MONTH])],
            'discount_percent' => ['nullable', 'numeric', 'gt:0', 'max:100', 'required_with:discount_days'],
            'discount_days' => ['nullable', 'integer', 'min:0', 'lte:due_days', 'required_with:discount_percent'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
    }
}
