<?php

namespace Modules\Sales\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;
use Modules\Finance\Controllers\Concerns\FiltersDocumentLists;
use Modules\Finance\Controllers\Controller;
use Modules\Sales\Controllers\Concerns\HandlesSalesLines;
use Modules\Sales\Models\Quotation;
use Modules\Sales\Services\QuotationService;

class QuotationController extends Controller
{
    use FiltersDocumentLists, HandlesSalesLines;

    public function __construct(
        CompanyContextService $companyContext,
        PermissionService $permissionService,
        protected QuotationService $quotations,
    ) {
        parent::__construct($companyContext, $permissionService);
    }

    public function index(Request $request): View
    {
        $query = Quotation::with(['customer', 'currency']);
        $filters = $this->applyListFilters($query, $request, 'quotation_date', ['quotation_number', 'customer_reference'], 'customer');

        return view('sales.quotations.index', [
            'quotations' => $query->orderByDesc('quotation_date')->orderByDesc('id')->paginate(20)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('sales.quotations.create', ['quotation' => null, ...$this->salesFormData()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $quotation = $this->quotations->create([...$this->validated($request), 'company_id' => $this->getActiveCompanyId(), 'branch_id' => $this->getActiveBranchId()]);

        return redirect()->route('sales.quotations.show', $quotation->id)->with('success', __('Quotation :number saved.', ['number' => $quotation->quotation_number]));
    }

    public function show(int $id): View
    {
        $quotation = Quotation::with(['customer', 'currency', 'lines.product.unit', 'lines.tax', 'salesOrder', 'createdBy'])->findOrFail($id);

        return view('sales.quotations.show', [...$this->salesFormData(), 'quotation' => $quotation]);
    }

    public function edit(int $id): View|RedirectResponse
    {
        $quotation = Quotation::with('lines')->findOrFail($id);

        if (! $quotation->isEditable()) {
            return redirect()->route('sales.quotations.show', $id)->with('error', __('Only draft or sent quotations can be edited.'));
        }

        return view('sales.quotations.edit', ['quotation' => $quotation, ...$this->salesFormData()]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->quotations->update(Quotation::findOrFail($id), $this->validated($request));

        return redirect()->route('sales.quotations.show', $id)->with('success', __('Quotation updated.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->quotations->delete(Quotation::findOrFail($id));

        return redirect()->route('sales.quotations.index')->with('success', __('Quotation deleted.'));
    }

    public function send(int $id): RedirectResponse
    {
        $this->quotations->markSent(Quotation::findOrFail($id));

        return back()->with('success', __('Quotation marked as sent.'));
    }

    public function accept(int $id): RedirectResponse
    {
        $this->quotations->accept(Quotation::findOrFail($id));

        return back()->with('success', __('Quotation accepted. Turn it into a sales order to deliver it.'));
    }

    public function decline(int $id): RedirectResponse
    {
        $this->quotations->decline(Quotation::findOrFail($id));

        return back()->with('success', __('Quotation declined.'));
    }

    public function convert(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'order_date' => ['required', 'date'],
            'delivery_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('company_id', $this->getActiveCompanyId())->where('status', 'active')],
        ]);

        $order = $this->quotations->convert(Quotation::with('lines')->findOrFail($id), $validated);

        return redirect()->route('sales.orders.show', $order->id)->with('success', __('Sales order :number created from the quotation. Confirm it to deliver.', ['number' => $order->order_number]));
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        $companyId = $this->getActiveCompanyId();

        return $request->validate([
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('company_id', $companyId)],
            'customer_reference' => ['nullable', 'string', 'max:100'],
            'quotation_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:quotation_date'],
            'currency_id' => ['nullable', Rule::exists('currencies', 'id')],
            'notes' => ['nullable', 'string', 'max:2000'],
            ...$this->salesLineRules($companyId),
        ], [], $this->salesLineAttributes());
    }
}
