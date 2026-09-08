<?php

namespace Modules\Core\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Models\Currency;
use Modules\Core\Models\ExchangeRate;
use Modules\Core\Services\CompanyContextService;

class ExchangeRateController extends Controller
{
    public function __construct(protected CompanyContextService $companyContext) {}

    public function index()
    {
        $companyId = $this->companyContext->getActiveCompanyId();
        $rates = ExchangeRate::where('company_id', $companyId)
            ->with('currency')
            ->orderBy('rate_date', 'desc')
            ->get();

        $currencies = Currency::active()->get();

        return view('core.exchange-rates.index', compact('rates', 'currencies'));
    }

    public function create()
    {
        $currencies = Currency::active()->get();

        return view('core.exchange-rates.create', compact('currencies'));
    }

    public function store(Request $request)
    {
        $companyId = $this->companyContext->getActiveCompanyId();

        $validated = $request->validate([
            'currency_id' => 'required|exists:currencies,id',
            'rate_date' => 'required|date',
            'exchange_rate' => 'required|numeric|min:0.0001',
            'source' => 'nullable|string|max:255',
        ]);

        $validated['company_id'] = $companyId;
        $validated['created_by'] = auth()->id();
        $validated['status'] = 'active';

        ExchangeRate::create($validated);

        return redirect()->route('core.exchange-rates.index')
            ->with('success', 'Exchange rate created successfully.');
    }

    public function edit(int $id)
    {
        $rate = ExchangeRate::findOrFail($id);
        $currencies = Currency::active()->get();

        return view('core.exchange-rates.edit', compact('rate', 'currencies'));
    }

    public function update(Request $request, int $id)
    {
        $rate = ExchangeRate::findOrFail($id);

        $validated = $request->validate([
            'currency_id' => 'required|exists:currencies,id',
            'rate_date' => 'required|date',
            'exchange_rate' => 'required|numeric|min:0.0001',
            'source' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $rate->update($validated);

        return redirect()->route('core.exchange-rates.index')
            ->with('success', 'Exchange rate updated successfully.');
    }

    public function destroy(int $id)
    {
        $rate = ExchangeRate::findOrFail($id);
        $rate->delete();

        return redirect()->route('core.exchange-rates.index')
            ->with('success', 'Exchange rate deleted successfully.');
    }
}
