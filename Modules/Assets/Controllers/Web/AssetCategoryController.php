<?php

namespace Modules\Assets\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Assets\Models\AssetCategory;
use Modules\Assets\Models\FixedAsset;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Models\Account;

class AssetCategoryController extends Controller
{
    public function index(): View
    {
        return view('assets.categories.index', [
            'categories' => AssetCategory::with(['assetAccount', 'accumulatedDepreciationAccount', 'depreciationExpenseAccount', 'disposalAccount'])->withCount('assets')->orderBy('code')->get(),
            'accounts' => Account::postable()->active()->whereIn('account_type', ['ASSET', 'EXPENSE', 'REVENUE'])->orderBy('account_code')->get(['id', 'account_code', 'account_name', 'account_type'])->groupBy('account_type'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        AssetCategory::create([...$this->validated($request), 'company_id' => $this->getActiveCompanyId()]);

        return back()->with('success', __('Category added.'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $category = AssetCategory::findOrFail($id);
        $category->update($this->validated($request, $category->id));

        return back()->with('success', __('Category updated.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $category = AssetCategory::findOrFail($id);

        if (FixedAsset::where('category_id', $id)->exists()) {
            return back()->with('error', __('Assets belong to this category. Make it inactive instead.'));
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
        $account = fn (array $types) => ['required', Rule::exists('accounts', 'id')->where('company_id', $companyId)->where('is_postable', 1)->whereIn('account_type', $types)];

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::unique('asset_categories', 'code')->where('company_id', $companyId)->ignore($ignoreId)],
            'name' => ['required', 'string', 'max:255'],
            'asset_account_id' => $account(['ASSET']),
            'accumulated_depreciation_account_id' => $account(['ASSET']),
            'depreciation_expense_account_id' => $account(['EXPENSE']),
            'disposal_account_id' => $account(['EXPENSE', 'REVENUE']),
            'depreciation_method' => ['required', Rule::in(AssetCategory::METHODS)],
            'useful_life_months' => ['required', 'integer', 'min:1', 'max:1200'],
            'declining_rate' => ['nullable', 'required_if:depreciation_method,declining_balance', 'numeric', 'gt:0', 'max:100'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        return [...$validated, 'declining_rate' => $validated['depreciation_method'] === AssetCategory::METHOD_DECLINING_BALANCE ? $validated['declining_rate'] : null];
    }
}
