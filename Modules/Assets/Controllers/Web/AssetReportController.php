<?php

namespace Modules\Assets\Controllers\Web;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Assets\Models\AssetCategory;
use Modules\Assets\Services\AssetReportService;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Controllers\Controller;

class AssetReportController extends Controller
{
    public function register(Request $request, AssetReportService $reports): View
    {
        $validated = $request->validate(['date' => ['nullable', 'date'], 'category' => ['nullable', 'integer']]);
        $date = isset($validated['date']) ? CarbonImmutable::parse($validated['date']) : app(CompanyContextService::class)->today();

        return view('assets.reports.register', [
            'rows' => $reports->register($date, $validated['category'] ?? null),
            'date' => $date,
            'category' => $validated['category'] ?? null,
            'categories' => AssetCategory::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function movements(Request $request, AssetReportService $reports): View
    {
        $today = app(CompanyContextService::class)->today();
        $validated = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);
        $from = isset($validated['from']) ? CarbonImmutable::parse($validated['from']) : $today->startOfYear();
        $to = isset($validated['to']) ? CarbonImmutable::parse($validated['to']) : $today;

        return view('assets.reports.movements', ['rows' => $reports->movements($from, $to), 'from' => $from, 'to' => $to]);
    }

    public function forecast(AssetReportService $reports): View
    {
        $start = app(CompanyContextService::class)->today()->startOfMonth();

        return view('assets.reports.forecast', ['forecast' => $reports->forecast($start), 'start' => $start]);
    }
}
