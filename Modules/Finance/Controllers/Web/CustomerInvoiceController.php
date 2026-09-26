<?php

namespace Modules\Finance\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\Core\Models\Currency;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;
use Modules\Finance\Controllers\Concerns\FiltersDocumentLists;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\Customer;
use Modules\Finance\Models\CustomerInvoice;
use Modules\Finance\Models\Tax;
use Modules\Finance\Requests\StoreCustomerInvoiceRequest;
use Modules\Finance\Services\CustomerInvoiceService;

class CustomerInvoiceController extends Controller
{
    use FiltersDocumentLists;

    public function __construct(
        protected CustomerInvoiceService $invoiceService,
        CompanyContextService $companyContext,
        PermissionService $permissionService
    ) {
        parent::__construct($companyContext, $permissionService);
    }

    public function index(Request $request)
    {

        $query = CustomerInvoice::with(['customer', 'currency']);
        $filters = $this->applyListFilters($query, $request, 'invoice_date', ['invoice_number', 'description'], 'customer');
        $invoices = $query
            ->when($filters['overdue'] ?? false, fn ($query) => $query->pending()->whereDate('due_date', '<', app(CompanyContextService::class)->today()->toDateString()))
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('finance.customer-invoices.index', compact('invoices', 'filters'));
    }

    public function create()
    {
        return view('finance.customer-invoices.create', $this->formData());
    }

    public function store(StoreCustomerInvoiceRequest $request): RedirectResponse
    {
        $invoice = $this->invoiceService->createInvoice([
            ...$request->validated(),
            'company_id' => $this->getActiveCompanyId(),
        ]);

        return redirect()
            ->route('finance.customer-invoices.show', $invoice->id)
            ->with('success', __('Invoice :number created.', ['number' => $invoice->invoice_number]));
    }

    public function show(string $id)
    {
        $invoice = CustomerInvoice::with(['customer', 'currency', 'lines.account', 'lines.tax'])->findOrFail($id);

        return view('finance.customer-invoices.show', compact('invoice'));
    }

    public function edit(string $id)
    {
        $invoice = CustomerInvoice::with('lines')->findOrFail($id);

        if (! $invoice->isDraft()) {
            return redirect()->route('finance.customer-invoices.show', $id)
                ->with('error', 'Only draft invoices can be edited.');
        }

        return view('finance.customer-invoices.edit', ['invoice' => $invoice, ...$this->formData()]);
    }

    public function update(StoreCustomerInvoiceRequest $request, string $id): RedirectResponse
    {
        $invoice = CustomerInvoice::findOrFail($id);

        if (! $invoice->isDraft()) {
            return redirect()->route('finance.customer-invoices.show', $id)
                ->with('error', 'Only draft invoices can be edited.');
        }

        $this->invoiceService->updateInvoice($invoice, $request->validated());

        return redirect()->route('finance.customer-invoices.show', $id)
            ->with('success', 'Invoice updated successfully');
    }

    /**
     * Customers, postable accounts, tax codes and currencies for the invoice form.
     *
     * @return array<string, Collection<int, mixed>>
     */
    protected function formData(): array
    {
        return [
            'customers' => Customer::with('paymentTerm')->where('status', 'active')->orderBy('name')->get(),
            'accounts' => Account::postable()->active()->orderBy('account_code')->get(['id', 'account_code', 'account_name']),
            'taxes' => Tax::where('status', 'active')->orderBy('tax_code')->get(),
            'currencies' => Currency::where('status', 'active')->orderBy('code')->get(),
        ];
    }

    public function destroy(string $id): RedirectResponse
    {

        $invoice = CustomerInvoice::findOrFail($id);

        if (! $invoice->isDraft()) {
            return redirect()->route('finance.customer-invoices.index')
                ->with('error', 'Only draft invoices can be deleted.');
        }

        $invoice->delete();

        return redirect()->route('finance.customer-invoices.index')
            ->with('success', 'Invoice deleted successfully');
    }

    public function submit(string $id)
    {

        $invoice = CustomerInvoice::findOrFail($id);

        if (! $invoice->isDraft()) {
            return back()->with('error', 'Only draft invoices can be submitted.');
        }

        $invoice = $this->invoiceService->submitInvoice($invoice);

        return back()->with('success', 'Invoice submitted successfully.');
    }

    public function approve(string $id)
    {

        $invoice = CustomerInvoice::findOrFail($id);

        if (! $invoice->isSubmitted()) {
            return back()->with('error', 'Only submitted invoices can be approved.');
        }

        $invoice = $this->invoiceService->approveInvoice($invoice);

        return back()->with('success', 'Invoice approved successfully.');
    }

    public function reject(Request $request, string $id)
    {

        $invoice = CustomerInvoice::findOrFail($id);

        if (! $invoice->isSubmitted()) {
            return back()->with('error', 'Only submitted invoices can be rejected.');
        }

        $invoice = $this->invoiceService->rejectInvoice($invoice, $request->get('reason'));

        return back()->with('success', 'Invoice rejected.');
    }

    public function post(string $id): RedirectResponse
    {

        $invoice = CustomerInvoice::findOrFail($id);

        if (! $invoice->isDraft() && ! $invoice->isSubmitted() && ! $invoice->isApproved()) {
            return back()->with('error', 'Only draft, submitted, or approved invoices can be posted.');
        }

        try {
            $this->invoiceService->postInvoice($invoice);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to post invoice: '.$e->getMessage());
        }

        return back()->with('success', 'Invoice posted successfully.');
    }

    public function cancel(string $id): RedirectResponse
    {

        $invoice = CustomerInvoice::findOrFail($id);

        if ($invoice->isPosted() || $invoice->isPaid()) {
            return back()->with('error', 'Posted or paid invoices cannot be cancelled.');
        }

        try {
            $this->invoiceService->cancelInvoice($invoice);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to cancel invoice: '.$e->getMessage());
        }

        return back()->with('success', 'Invoice cancelled successfully.');
    }
}
