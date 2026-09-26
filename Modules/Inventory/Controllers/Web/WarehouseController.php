<?php

namespace Modules\Inventory\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Core\Models\Branch;
use Modules\Finance\Controllers\Controller;
use Modules\Inventory\Models\Warehouse;

class WarehouseController extends Controller
{
    public function index(): View
    {
        return view('inventory.warehouses.index', [
            'warehouses' => Warehouse::with('branch')->orderByDesc('is_default')->orderBy('code')->get(),
            'branches' => Branch::where('company_id', $this->getActiveCompanyId())->orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Warehouse::create([...$this->validated($request), 'company_id' => $this->getActiveCompanyId()]);

        return back()->with('success', __('Warehouse added.'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $warehouse = Warehouse::findOrFail($id);
        $validated = $this->validated($request, $warehouse->id);

        if ($warehouse->is_default && (! $validated['is_default'] || $validated['status'] !== 'active')) {
            return back()->with('error', __('Make another warehouse the default first.'));
        }

        $warehouse->update($validated);

        return back()->with('success', __('Warehouse updated.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $warehouse = Warehouse::findOrFail($id);

        if ($warehouse->is_default || $warehouse->isInUse()) {
            return back()->with('error', __('This warehouse is the default or holds stock history. Make it inactive instead.'));
        }

        $warehouse->delete();

        return back()->with('success', __('Warehouse deleted.'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?int $ignoreId = null): array
    {
        $companyId = $this->getActiveCompanyId();

        return [
            ...$request->validate([
                'code' => ['required', 'string', 'max:20', Rule::unique('warehouses', 'code')->where('company_id', $companyId)->ignore($ignoreId)],
                'name' => ['required', 'string', 'max:255'],
                'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('company_id', $companyId)],
                'address' => ['nullable', 'string', 'max:1000'],
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ]),
            'is_default' => $request->boolean('is_default'),
        ];
    }
}
