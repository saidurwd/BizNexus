<?php

namespace Modules\Finance\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Finance\Models\CostCenter;

class CostCenterController extends Controller
{
    public function index()
    {
        $costCenters = CostCenter::with(['parent', 'manager'])
            ->orderBy('code')
            ->get();

        return view('finance::cost-centers.index', compact('costCenters'));
    }

    public function create()
    {
        $parentOptions = CostCenter::where('status', 'active')
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return view('finance::cost-centers.create', compact('parentOptions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:cost_centers,code',
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|integer|exists:cost_centers,id',
            'status' => 'required|in:active,inactive',
        ]);

        CostCenter::create($validated);

        return redirect()->route('finance.cost-centers.index')
            ->with('success', 'Cost Center created successfully.');
    }

    public function edit(int $id)
    {
        $costCenter = CostCenter::findOrFail($id);
        $parentOptions = CostCenter::where('status', 'active')
            ->where('id', '!=', $id)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return view('finance::cost-centers.edit', compact('costCenter', 'parentOptions'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $costCenter = CostCenter::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:cost_centers,code,' . $id,
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|integer|exists:cost_centers,id',
            'status' => 'required|in:active,inactive',
        ]);

        $costCenter->update($validated);

        return redirect()->route('finance.cost-centers.index')
            ->with('success', 'Cost Center updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $costCenter = CostCenter::findOrFail($id);
        $costCenter->delete();

        return redirect()->route('finance.cost-centers.index')
            ->with('success', 'Cost Center deleted successfully.');
    }
}
