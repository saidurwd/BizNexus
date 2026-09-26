<?php

namespace Modules\Finance\Controllers\Web;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Models\Currency;
use Modules\Finance\Controllers\Concerns\FiltersDocumentLists;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\Customer;
use Modules\Finance\Models\CustomerCreditNote;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Models\Tax;
use Modules\Finance\Requests\StoreCustomerCreditNoteRequest;
use Modules\Finance\Services\CustomerCreditNoteService;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\Warehouse;

class CustomerCreditNoteController extends Controller
{
    use FiltersDocumentLists;

    public function index(Request $request): View
    {
        $query = CustomerCreditNote::with(['customer', 'currency', 'invoice']);
        $filters = $this->applyListFilters($query, $request, 'note_date', ['note_number', 'description'], 'customer');
        $creditNotes = $query->orderByDesc('note_date')->orderByDesc('id')->paginate(20)->withQueryString();

        return view('finance.customer-credit-notes.index', compact('creditNotes', 'filters'));
    }

    /**
     * A new credit note, prefilled from ?invoice= with that invoice's customer, currency and lines.
     */
    public function create(Request $request): View
    {
        $invoice = $request->filled('invoice')
            ? CustomerInvoice::with('lines')->whereIn('status', [CustomerInvoice::STATUS_POSTED, CustomerInvoice::STATUS_PARTIALLY_PAID, CustomerInvoice::STATUS_PAID])->findOrFail($request->integer('invoice'))
            : null;

        return view('finance.customer-credit-notes.create', ['invoice' => $invoice, ...$this->formData()]);
    }

    public function store(StoreCustomerCreditNoteRequest $request, CustomerCreditNoteService $service): RedirectResponse
    {
        $creditNote = $service->create([...$request->validated(), 'company_id' => $this->getActiveCompanyId()]);

        return redirect()->route('finance.customer-credit-notes.show', $creditNote->id)
            ->with('success', __('Credit note :number saved as a draft.', ['number' => $creditNote->note_number]));
    }

    public function show(int $id): View
    {
        $creditNote = CustomerCreditNote::with(['customer', 'currency', 'invoice', 'lines.account', 'lines.tax', 'journal'])->findOrFail($id);

        return view('finance.customer-credit-notes.show', compact('creditNote'));
    }

    public function edit(int $id): View|RedirectResponse
    {
        $creditNote = CustomerCreditNote::with('lines')->findOrFail($id);

        if (! in_array($creditNote->status, [CustomerCreditNote::STATUS_DRAFT, CustomerCreditNote::STATUS_REJECTED], true)) {
            return redirect()->route('finance.customer-credit-notes.show', $id)->with('error', __('Only draft or rejected credit notes can be edited.'));
        }

        return view('finance.customer-credit-notes.edit', ['creditNote' => $creditNote, ...$this->formData()]);
    }

    public function update(StoreCustomerCreditNoteRequest $request, int $id, CustomerCreditNoteService $service): RedirectResponse
    {
        return $this->act($id, fn (CustomerCreditNote $creditNote) => $service->update($creditNote, $request->validated()), __('Credit note updated.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $creditNote = CustomerCreditNote::findOrFail($id);

        if (! $creditNote->isDraft()) {
            return back()->with('error', __('Only draft credit notes can be deleted.'));
        }

        $creditNote->delete();

        return redirect()->route('finance.customer-credit-notes.index')->with('success', __('Credit note deleted.'));
    }

    public function submit(int $id, CustomerCreditNoteService $service): RedirectResponse
    {
        return $this->act($id, fn (CustomerCreditNote $creditNote) => $service->submit($creditNote), __('Credit note submitted for approval.'));
    }

    public function approve(int $id, CustomerCreditNoteService $service): RedirectResponse
    {
        return $this->act($id, fn (CustomerCreditNote $creditNote) => $service->approve($creditNote), __('Credit note approved.'));
    }

    public function reject(Request $request, int $id, CustomerCreditNoteService $service): RedirectResponse
    {
        $reason = $request->validate(['reason' => ['nullable', 'string', 'max:1000']])['reason'] ?? null;

        return $this->act($id, fn (CustomerCreditNote $creditNote) => $service->reject($creditNote, $reason), __('Credit note rejected.'));
    }

    public function post(int $id, CustomerCreditNoteService $service): RedirectResponse
    {
        return $this->act($id, fn (CustomerCreditNote $creditNote) => $service->post($creditNote), __('Credit note posted.'));
    }

    public function cancel(int $id, CustomerCreditNoteService $service): RedirectResponse
    {
        return $this->act($id, fn (CustomerCreditNote $creditNote) => $service->cancel($creditNote), __('Credit note cancelled.'));
    }

    /**
     * Run a lifecycle action and return to the credit note with its outcome.
     *
     * @param  Closure(CustomerCreditNote): mixed  $action
     */
    protected function act(int $id, Closure $action, string $success): RedirectResponse
    {
        $creditNote = CustomerCreditNote::findOrFail($id);

        try {
            $action($creditNote);
        } catch (InvalidAccountingTransactionException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('finance.customer-credit-notes.show', $id)->with('success', $success);
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(): array
    {
        return [
            'customers' => Customer::where('status', 'active')->orderBy('name')->get(),
            'invoices' => CustomerInvoice::with('currency')
                ->whereIn('status', [CustomerInvoice::STATUS_POSTED, CustomerInvoice::STATUS_PARTIALLY_PAID, CustomerInvoice::STATUS_PAID])
                ->orderByDesc('invoice_date')
                ->get(['id', 'customer_id', 'invoice_number', 'invoice_date', 'currency_id', 'total_amount', 'outstanding_amount']),
            'accounts' => Account::postable()->active()->orderBy('account_code')->get(['id', 'account_code', 'account_name']),
            'taxes' => Tax::where('status', 'active')->orderBy('tax_code')->get(),
            'currencies' => Currency::where('status', 'active')->orderBy('code')->get(),
            'products' => Product::active()->with(['category', 'unit'])->orderBy('sku')->get(),
            'warehouses' => Warehouse::active()->orderByDesc('is_default')->orderBy('code')->get(['id', 'code', 'name']),
        ];
    }
}
