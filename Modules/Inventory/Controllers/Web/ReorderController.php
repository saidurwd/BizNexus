<?php

namespace Modules\Inventory\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Models\Supplier;
use Modules\Inventory\Models\Warehouse;
use Modules\Inventory\Services\ReorderService;

class ReorderController extends Controller
{
    public function index(ReorderService $reorder): View
    {
        return view('inventory.reorder.index', [
            'rows' => $reorder->suggestions(),
            'suppliers' => Supplier::where('status', 'active')->orderBy('name')->get(['id', 'supplier_code', 'name']),
            'warehouses' => Warehouse::active()->orderByDesc('is_default')->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request, ReorderService $reorder): RedirectResponse
    {
        $companyId = $this->getActiveCompanyId();
        $validated = $request->validate([
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('company_id', $companyId)->where('status', 'active')],
            'lines' => ['required', 'array'],
            'lines.*.selected' => ['nullable', 'boolean'],
            'lines.*.quantity' => ['nullable', 'numeric'],
            'lines.*.supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where('company_id', $companyId)],
        ]);

        $lines = collect($validated['lines'])->filter(fn (array $line) => ! empty($line['selected']));

        if ($lines->isEmpty()) {
            return back()->withInput()->with('error', __('Choose at least one product to order.'));
        }

        $errors = $lines->filter(fn (array $line) => empty($line['supplier_id']) || ! is_numeric($line['quantity'] ?? null) || $line['quantity'] <= 0);

        if ($errors->isNotEmpty()) {
            return back()->withInput()->with('error', __('Each chosen product needs a supplier and a quantity above zero.'));
        }

        $orders = $reorder->createOrders($companyId, (int) $validated['warehouse_id'], app(CompanyContextService::class)->today()->toDateString(), $lines->all(), $this->getActiveBranchId());

        return redirect()->route('inventory.purchase-orders.index', ['status' => 'DRAFT'])
            ->with('success', trans_choice('Created :count draft purchase order: :numbers.|Created :count draft purchase orders: :numbers.', $orders->count(), ['count' => $orders->count(), 'numbers' => $orders->pluck('order_number')->implode(', ')]));
    }
}
