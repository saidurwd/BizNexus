<?php

namespace Modules\Assets\Controllers\Web;

use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Assets\Models\AssetDepreciationRun;
use Modules\Assets\Services\DepreciationService;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Controllers\Controller;

class DepreciationRunController extends Controller
{
    public function index(Request $request, DepreciationService $depreciation): View
    {
        $validated = $request->validate(['period' => ['nullable', 'date_format:Y-m']]);
        $period = isset($validated['period'])
            ? CarbonImmutable::createFromFormat('Y-m-d', $validated['period'].'-01')->endOfMonth()->startOfDay()
            : app(CompanyContextService::class)->today()->startOfMonth()->subDay()->startOfDay();

        return view('assets.depreciation.index', [
            'runs' => AssetDepreciationRun::with(['journal', 'createdBy'])->orderByDesc('period_end')->orderByDesc('id')->paginate(20),
            'period' => $period,
            'preview' => $depreciation->preview($period),
        ]);
    }

    public function store(Request $request, DepreciationService $depreciation): RedirectResponse
    {
        $validated = $request->validate(['period' => ['required', 'date_format:Y-m']]);
        $run = $depreciation->run(CarbonImmutable::createFromFormat('Y-m-d', $validated['period'].'-01'));

        return redirect()->route('assets.depreciation.show', $run->id)->with('success', __('Depreciation for :month posted.', ['month' => $run->period_end->translatedFormat('F Y')]));
    }

    public function show(int $id): View
    {
        $run = AssetDepreciationRun::with(['journal', 'reversalJournal', 'createdBy', 'transactions.asset.category'])->findOrFail($id);

        return view('assets.depreciation.show', compact('run'));
    }

    public function reverse(Request $request, int $id, DepreciationService $depreciation): RedirectResponse
    {
        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $depreciation->reverse(AssetDepreciationRun::findOrFail($id), $validated['reason'] ?? null);

        return back()->with('success', __('Depreciation run reversed.'));
    }
}
