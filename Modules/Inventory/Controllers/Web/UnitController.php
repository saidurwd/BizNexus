<?php

namespace Modules\Inventory\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Finance\Controllers\Controller;
use Modules\Inventory\Models\Product;
use Modules\Inventory\Models\Unit;

class UnitController extends Controller
{
    public function index(): View
    {
        return view('inventory.units.index', [
            'units' => Unit::withCount('products')->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Unit::create([...$this->validated($request), 'company_id' => $this->getActiveCompanyId()]);

        return back()->with('success', __('Unit added.'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $unit = Unit::findOrFail($id);
        $unit->update($this->validated($request, $unit->id));

        return back()->with('success', __('Unit updated.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $unit = Unit::findOrFail($id);

        if (Product::where('unit_id', $id)->exists()) {
            return back()->with('error', __('Products are measured in this unit. Make it inactive instead.'));
        }

        $unit->delete();

        return back()->with('success', __('Unit deleted.'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:10', Rule::unique('units_of_measure', 'code')->where('company_id', $this->getActiveCompanyId())->ignore($ignoreId)],
            'name' => ['required', 'string', 'max:100'],
            'decimals' => ['required', 'integer', 'min:0', 'max:4'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
    }
}
