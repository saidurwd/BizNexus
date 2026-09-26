<?php

namespace Modules\Finance\Controllers\Web;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Models\Currency;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\Supplier;
use Modules\Finance\Models\SupplierCreditNote;
use Modules\Finance\Models\SupplierInvoice;
use Modules\Finance\Models\Tax;
use Modules\Finance\Requests\StoreSupplierCreditNoteRequest;
use Modules\Finance\Services\SupplierCreditNoteService;

class SupplierCreditNoteController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');

        $creditNotes = SupplierCreditNote::with(['supplier', 'currency', 'invoice'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('credit_note_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('finance.supplier-credit-notes.index', compact('creditNotes', 'status'));
    }

    /**
     * A new credit note, prefilled from ?invoice= with that invoice's supplier, currency and lines.
     */
    public function create(Request $request): View
    {
        $invoice = $request->filled('invoice')
            ? SupplierInvoice::with('lines')->whereIn('status', [SupplierInvoice::STATUS_POSTED, SupplierInvoice::STATUS_PARTIALLY_PAID, SupplierInvoice::STATUS_PAID])->findOrFail($request->integer('invoice'))
            : null;

        return view('finance.supplier-credit-notes.create', ['invoice' => $invoice, ...$this->formData()]);
    }

    public function store(StoreSupplierCreditNoteRequest $request, SupplierCreditNoteService $service): RedirectResponse
    {
        $creditNote = $service->create([...$request->validated(), 'company_id' => $this->getActiveCompanyId()]);

        return redirect()->route('finance.supplier-credit-notes.show', $creditNote->id)
            ->with('success', __('Credit note :number saved as a draft.', ['number' => $creditNote->credit_note_number]));
    }

    public function show(int $id): View
    {
        $creditNote = SupplierCreditNote::with(['supplier', 'currency', 'invoice', 'lines.account', 'lines.tax', 'journal'])->findOrFail($id);

        return view('finance.supplier-credit-notes.show', compact('creditNote'));
    }

    public function edit(int $id): View|RedirectResponse
    {
        $creditNote = SupplierCreditNote::with('lines')->findOrFail($id);

        if (! in_array($creditNote->status, [SupplierCreditNote::STATUS_DRAFT, SupplierCreditNote::STATUS_REJECTED], true)) {
            return redirect()->route('finance.supplier-credit-notes.show', $id)->with('error', __('Only draft or rejected credit notes can be edited.'));
        }

        return view('finance.supplier-credit-notes.edit', ['creditNote' => $creditNote, ...$this->formData()]);
    }

    public function update(StoreSupplierCreditNoteRequest $request, int $id, SupplierCreditNoteService $service): RedirectResponse
    {
        return $this->act($id, fn (SupplierCreditNote $creditNote) => $service->update($creditNote, $request->validated()), __('Credit note updated.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $creditNote = SupplierCreditNote::findOrFail($id);

        if (! $creditNote->isDraft()) {
            return back()->with('error', __('Only draft credit notes can be deleted.'));
        }

        $creditNote->delete();

        return redirect()->route('finance.supplier-credit-notes.index')->with('success', __('Credit note deleted.'));
    }

    public function submit(int $id, SupplierCreditNoteService $service): RedirectResponse
    {
        return $this->act($id, fn (SupplierCreditNote $creditNote) => $service->submit($creditNote), __('Credit note submitted for approval.'));
    }

    public function approve(int $id, SupplierCreditNoteService $service): RedirectResponse
    {
        return $this->act($id, fn (SupplierCreditNote $creditNote) => $service->approve($creditNote), __('Credit note approved.'));
    }

    public function reject(Request $request, int $id, SupplierCreditNoteService $service): RedirectResponse
    {
        $reason = $request->validate(['reason' => ['nullable', 'string', 'max:1000']])['reason'] ?? null;

        return $this->act($id, fn (SupplierCreditNote $creditNote) => $service->reject($creditNote, $reason), __('Credit note rejected.'));
    }

    public function post(int $id, SupplierCreditNoteService $service): RedirectResponse
    {
        return $this->act($id, fn (SupplierCreditNote $creditNote) => $service->post($creditNote), __('Credit note posted.'));
    }

    public function cancel(int $id, SupplierCreditNoteService $service): RedirectResponse
    {
        return $this->act($id, fn (SupplierCreditNote $creditNote) => $service->cancel($creditNote), __('Credit note cancelled.'));
    }

    /**
     * Run a lifecycle action and return to the credit note with its outcome.
     *
     * @param  Closure(SupplierCreditNote): mixed  $action
     */
    protected function act(int $id, Closure $action, string $success): RedirectResponse
    {
        $creditNote = SupplierCreditNote::findOrFail($id);

        try {
            $action($creditNote);
        } catch (InvalidAccountingTransactionException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('finance.supplier-credit-notes.show', $id)->with('success', $success);
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(): array
    {
        return [
            'suppliers' => Supplier::where('status', 'active')->orderBy('name')->get(),
            'invoices' => SupplierInvoice::with('currency')
                ->whereIn('status', [SupplierInvoice::STATUS_POSTED, SupplierInvoice::STATUS_PARTIALLY_PAID, SupplierInvoice::STATUS_PAID])
                ->orderByDesc('invoice_date')
                ->get(['id', 'supplier_id', 'invoice_number', 'invoice_date', 'currency_id', 'total_amount', 'outstanding_amount']),
            'accounts' => Account::postable()->active()->orderBy('account_code')->get(['id', 'account_code', 'account_name']),
            'taxes' => Tax::where('status', 'active')->orderBy('tax_code')->get(),
            'currencies' => Currency::where('status', 'active')->orderBy('code')->get(),
        ];
    }
}
