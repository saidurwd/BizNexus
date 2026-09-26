<?php

namespace Modules\Assets\Controllers\Web;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Assets\Models\AssetCategory;
use Modules\Assets\Models\FixedAsset;
use Modules\Assets\Services\FixedAssetService;
use Modules\Core\Models\Branch;
use Modules\Core\Models\Department;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\Supplier;

class FixedAssetController extends Controller
{
    public function __construct(
        CompanyContextService $companyContext,
        PermissionService $permissionService,
        protected FixedAssetService $assets,
    ) {
        parent::__construct($companyContext, $permissionService);
    }

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in(FixedAsset::STATUSES)],
            'branch' => ['nullable', 'integer'],
        ]);
        $term = isset($filters['q']) ? '%'.addcslashes($filters['q'], '%_\\').'%' : null;

        $assets = FixedAsset::with(['category', 'branch', 'department'])
            ->when($term, fn (Builder $query) => $query->where(fn (Builder $query) => $query->where('asset_number', 'like', $term)->orWhere('name', 'like', $term)->orWhere('serial_number', 'like', $term)->orWhere('tag', 'like', $term)->orWhere('custodian', 'like', $term)))
            ->when($filters['category'] ?? null, fn (Builder $query, int $category) => $query->where('category_id', $category))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status), fn (Builder $query) => $query->where('status', '!=', FixedAsset::STATUS_DISPOSED))
            ->when($filters['branch'] ?? null, fn (Builder $query, int $branch) => $query->where('branch_id', $branch))
            ->orderBy('asset_number')
            ->paginate(25)
            ->withQueryString();

        return view('assets.assets.index', [
            'assets' => $assets,
            'filters' => $filters,
            'categories' => AssetCategory::orderBy('name')->get(['id', 'code', 'name']),
            'branches' => $this->branches(),
        ]);
    }

    public function create(): View
    {
        return view('assets.assets.create', ['asset' => null, ...$this->formData()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $asset = $this->assets->register([...$this->validated($request), 'company_id' => $this->getActiveCompanyId()]);

        return redirect()->route('assets.assets.show', $asset->id)->with('success', __('Asset :number registered.', ['number' => $asset->asset_number]));
    }

    public function show(int $id): View
    {
        $asset = FixedAsset::with(['category', 'branch', 'department', 'supplier', 'transactions.journal', 'createdBy'])->findOrFail($id);

        return view('assets.assets.show', [
            'asset' => $asset,
            'branches' => $this->branches(),
            'departments' => Department::where('company_id', $this->getActiveCompanyId())->orderBy('name')->get(['id', 'name']),
            'proceedsAccounts' => Account::postable()->active()->where('account_type', 'ASSET')->orderBy('account_code')->get(['id', 'account_code', 'account_name']),
        ]);
    }

    public function edit(int $id): View
    {
        return view('assets.assets.edit', ['asset' => FixedAsset::findOrFail($id), ...$this->formData()]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $asset = FixedAsset::findOrFail($id);
        $this->assets->update($asset, $this->validated($request, $asset));

        return redirect()->route('assets.assets.show', $id)->with('success', __('Asset updated.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->assets->delete(FixedAsset::findOrFail($id));

        return redirect()->route('assets.assets.index')->with('success', __('Asset deleted.'));
    }

    public function transfer(Request $request, int $id): RedirectResponse
    {
        $companyId = $this->getActiveCompanyId();
        $validated = $request->validate([
            'transfer_date' => ['required', 'date'],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('company_id', $companyId)],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('company_id', $companyId)],
            'location' => ['nullable', 'string', 'max:255'],
            'custodian' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->assets->transfer(FixedAsset::findOrFail($id), $validated);

        return back()->with('success', __('Asset transferred.'));
    }

    public function impair(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'impairment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->assets->impair(FixedAsset::findOrFail($id), $validated['impairment_date'], (string) $validated['amount'], $validated['reason'] ?? null);

        return back()->with('success', __('Impairment posted.'));
    }

    public function dispose(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'disposal_date' => ['required', 'date'],
            'proceeds' => ['nullable', 'numeric', 'min:0'],
            'proceeds_account_id' => ['nullable', Rule::exists('accounts', 'id')->where('company_id', $this->getActiveCompanyId())->where('is_postable', 1)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $asset = $this->assets->dispose(FixedAsset::findOrFail($id), $validated['disposal_date'], (string) ($validated['proceeds'] ?? '0'), isset($validated['proceeds_account_id']) ? (int) $validated['proceeds_account_id'] : null, $validated['notes'] ?? null);

        return back()->with('success', __('Asset :number disposed of.', ['number' => $asset->asset_number]));
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?FixedAsset $asset = null): array
    {
        $companyId = $this->getActiveCompanyId();

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => [$asset ? 'nullable' : 'required', Rule::exists('asset_categories', 'id')->where('company_id', $companyId)],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('company_id', $companyId)],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('company_id', $companyId)],
            'location' => ['nullable', 'string', 'max:255'],
            'custodian' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'tag' => ['nullable', 'string', 'max:100'],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where('company_id', $companyId)],
            'acquisition_date' => [$asset ? 'nullable' : 'required', 'date'],
            'in_service_date' => ['nullable', 'date', 'after_or_equal:acquisition_date'],
            'cost' => [$asset ? 'nullable' : 'required', 'numeric', 'gt:0'],
            'residual_value' => ['nullable', 'numeric', 'min:0'],
            'depreciation_method' => ['nullable', Rule::in(AssetCategory::METHODS)],
            'useful_life_months' => ['nullable', 'integer', 'min:1', 'max:1200'],
            'declining_rate' => ['nullable', 'numeric', 'gt:0', 'max:100'],
            'opening_accumulated_depreciation' => ['nullable', 'numeric', 'min:0'],
            'depreciated_until' => ['nullable', 'date'],
            'offset_account_id' => ['nullable', Rule::exists('accounts', 'id')->where('company_id', $companyId)->where('is_postable', 1)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(): array
    {
        $companyId = $this->getActiveCompanyId();

        return [
            'categories' => AssetCategory::active()->orderBy('name')->get(),
            'branches' => $this->branches(),
            'departments' => Department::where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
            'suppliers' => Supplier::where('status', 'active')->orderBy('name')->get(['id', 'supplier_code', 'name']),
            'offsetAccounts' => Account::postable()->active()->orderBy('account_code')->get(['id', 'account_code', 'account_name']),
        ];
    }

    protected function branches()
    {
        return Branch::where('company_id', $this->getActiveCompanyId())->orderBy('name')->get(['id', 'code', 'name']);
    }
}
