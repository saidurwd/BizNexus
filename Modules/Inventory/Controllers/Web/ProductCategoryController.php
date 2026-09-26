<?php

namespace Modules\Inventory\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Models\Account;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\ProductCategory;

class ProductCategoryController extends Controller
{
    public function index(): View
    {
        return view('inventory.categories.index', [
            'categories' => ProductCategory::with(['inventoryAccount', 'cogsAccount', 'revenueAccount', 'expenseAccount'])->withCount('products')->orderBy('code')->get(),
            'accounts' => Account::postable()->active()->whereIn('account_type', ['ASSET', 'EXPENSE', 'REVENUE'])
                ->orderBy('account_code')->get(['id', 'account_code', 'account_name', 'account_type'])->groupBy('account_type'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ProductCategory::create([...$this->validated($request), 'company_id' => $this->getActiveCompanyId()]);

        return back()->with('success', __('Category added.'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $category = ProductCategory::findOrFail($id);
        $category->update($this->validated($request, $category->id));

        return back()->with('success', __('Category updated.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $category = ProductCategory::findOrFail($id);

        if (Product::where('category_id', $id)->exists()) {
            return back()->with('error', __('Products belong to this category. Move them or make the category inactive.'));
        }

        $category->delete();

        return back()->with('success', __('Category deleted.'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?int $ignoreId = null): array
    {
        $companyId = $this->getActiveCompanyId();
        $account = fn (string $type) => ['nullable', Rule::exists('accounts', 'id')->where('company_id', $companyId)->where('is_postable', 1)->where('account_type', $type)];

        return $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('product_categories', 'code')->where('company_id', $companyId)->ignore($ignoreId)],
            'name' => ['required', 'string', 'max:255'],
            'inventory_account_id' => $account('ASSET'),
            'cogs_account_id' => $account('EXPENSE'),
            'revenue_account_id' => $account('REVENUE'),
            'expense_account_id' => $account('EXPENSE'),
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
    }
}
