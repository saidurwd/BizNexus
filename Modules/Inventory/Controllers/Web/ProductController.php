<?php

namespace Modules\Inventory\Controllers\Web;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\Supplier;
use Modules\Finance\Models\Tax;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\ProductCategory;
use Modules\Inventory\Models\Unit;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::in(Product::TYPES)],
            'category' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'reorder' => ['nullable', 'boolean'],
        ]);
        $term = isset($filters['q']) ? '%'.addcslashes($filters['q'], '%_\\').'%' : null;

        $products = Product::with(['category', 'unit'])
            ->when($term, fn (Builder $query) => $query->where(fn (Builder $query) => $query->where('sku', 'like', $term)->orWhere('name', 'like', $term)->orWhere('barcode', 'like', $term)))
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('type', $type))
            ->when($filters['category'] ?? null, fn (Builder $query, int $category) => $query->where('category_id', $category))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['reorder'] ?? false, fn (Builder $query) => $query->where('type', Product::TYPE_STOCK)->whereNotNull('reorder_level')->whereColumn('stock_quantity', '<=', 'reorder_level'))
            ->orderBy('sku')
            ->paginate(25)
            ->withQueryString();

        return view('inventory.products.index', [
            'products' => $products,
            'filters' => $filters,
            'categories' => ProductCategory::orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    public function create(): View
    {
        return view('inventory.products.create', ['product' => null, ...$this->formData()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $product = Product::create([
            ...$this->validated($request),
            'company_id' => $this->getActiveCompanyId(),
            'costing_method' => Product::COSTING_WEIGHTED_AVERAGE,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('inventory.products.show', $product->id)->with('success', __('Product :sku created.', ['sku' => $product->sku]));
    }

    public function show(int $id): View
    {
        $product = Product::with(['category', 'unit', 'purchaseTax', 'salesTax', 'preferredSupplier', 'inventoryAccount', 'cogsAccount', 'revenueAccount', 'expenseAccount', 'category.inventoryAccount', 'category.cogsAccount', 'category.revenueAccount', 'category.expenseAccount'])
            ->findOrFail($id);

        return view('inventory.products.show', compact('product'));
    }

    public function edit(int $id): View
    {
        return view('inventory.products.edit', ['product' => Product::findOrFail($id), ...$this->formData()]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $product = Product::findOrFail($id);
        $validated = $this->validated($request, $product);

        $product->update([...$validated, 'updated_by' => Auth::id()]);

        return redirect()->route('inventory.products.show', $product->id)->with('success', __('Product updated.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $product = Product::findOrFail($id);

        if ($product->isInUse()) {
            return back()->with('error', __('This product has stock or appears on documents. Make it inactive instead.'));
        }

        $product->delete();

        return redirect()->route('inventory.products.index')->with('success', __('Product deleted.'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Product $product = null): array
    {
        $companyId = $this->getActiveCompanyId();
        $account = fn (string $type) => ['nullable', Rule::exists('accounts', 'id')->where('company_id', $companyId)->where('is_postable', 1)->where('account_type', $type)];

        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:50', Rule::unique('products', 'sku')->where('company_id', $companyId)->ignore($product?->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', Rule::in(Product::TYPES)],
            'category_id' => ['nullable', Rule::exists('product_categories', 'id')->where('company_id', $companyId)],
            'unit_id' => ['required', Rule::exists('units_of_measure', 'id')->where('company_id', $companyId)],
            'barcode' => ['nullable', 'string', 'max:50'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'sales_price' => ['nullable', 'numeric', 'min:0'],
            'purchase_tax_id' => ['nullable', Rule::exists('taxes', 'id')->where('company_id', $companyId)],
            'sales_tax_id' => ['nullable', Rule::exists('taxes', 'id')->where('company_id', $companyId)],
            'inventory_account_id' => $account('ASSET'),
            'cogs_account_id' => $account('EXPENSE'),
            'revenue_account_id' => $account('REVENUE'),
            'expense_account_id' => $account('EXPENSE'),
            'preferred_supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where('company_id', $companyId)],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'reorder_quantity' => ['nullable', 'numeric', 'gt:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        if ($product && $product->type !== $validated['type'] && $product->isInUse()) {
            throw ValidationException::withMessages(['type' => __('The type cannot change once the product has stock or appears on documents.')]);
        }

        return [...$validated, 'purchase_price' => $validated['purchase_price'] ?? 0, 'sales_price' => $validated['sales_price'] ?? 0];
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(): array
    {
        $accounts = Account::postable()->active()->whereIn('account_type', ['ASSET', 'EXPENSE', 'REVENUE'])
            ->orderBy('account_code')->get(['id', 'account_code', 'account_name', 'account_type'])->groupBy('account_type');

        return [
            'categories' => ProductCategory::active()->orderBy('name')->get(),
            'units' => Unit::active()->orderBy('code')->get(),
            'taxes' => Tax::where('status', 'active')->orderBy('tax_code')->get(['id', 'tax_code', 'tax_name']),
            'suppliers' => Supplier::where('status', 'active')->orderBy('name')->get(['id', 'supplier_code', 'name']),
            'accounts' => $accounts,
        ];
    }
}
