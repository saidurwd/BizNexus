<?php

namespace Modules\Core\Controllers\Web;

use App\Http\Controllers\Controller;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Core\Models\Currency;
use Modules\Core\Models\ExchangeRate;
use Modules\Core\Services\CompanyContextService;
use Modules\Finance\Enums\ExchangeRateType;

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

        $validated = $request->validate($this->rules($request, $companyId));

        $validated['company_id'] = $companyId;
        $validated['created_by'] = auth()->id();
        $validated['status'] = 'active';

        ExchangeRate::create($validated);

        return redirect()->route('core.exchange-rates.index')
            ->with('success', 'Exchange rate created successfully.');
    }

    /**
     * One rate per company, currency, rate type and date.
     *
     * @return array<string, mixed>
     */
    protected function rules(Request $request, int $companyId, ?int $ignoreId = null): array
    {
        return [
            'currency_id' => 'required|exists:currencies,id',
            'rate_type' => ['required', Rule::enum(ExchangeRateType::class)],
            'rate_date' => [
                'required', 'date',
                function (string $attribute, mixed $value, Closure $fail) use ($request, $companyId, $ignoreId) {
                    $exists = ExchangeRate::withoutGlobalScopes()
                        ->where('company_id', $companyId)
                        ->where('currency_id', $request->input('currency_id'))
                        ->where('rate_type', $request->input('rate_type'))
                        ->whereDate('rate_date', $value)
                        ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                        ->exists();

                    if ($exists) {
                        $fail('A rate of this type already exists for this currency and date.');
                    }
                },
            ],
            'exchange_rate' => 'required|numeric|gt:0',
            'source' => 'nullable|string|max:255',
        ];
    }

    public function edit(int $id)
    {
        $rate = ExchangeRate::where('company_id', $this->companyContext->getActiveCompanyId())->findOrFail($id);
        $currencies = Currency::active()->get();

        return view('core.exchange-rates.edit', compact('rate', 'currencies'));
    }

    public function update(Request $request, int $id)
    {
        $rate = ExchangeRate::where('company_id', $this->companyContext->getActiveCompanyId())->findOrFail($id);

        $validated = $request->validate([
            ...$this->rules($request, $rate->company_id, $rate->id),
            'status' => 'required|in:active,inactive',
        ]);

        $rate->update($validated);

        return redirect()->route('core.exchange-rates.index')
            ->with('success', 'Exchange rate updated successfully.');
    }

    public function destroy(int $id)
    {
        $rate = ExchangeRate::where('company_id', $this->companyContext->getActiveCompanyId())->findOrFail($id);
        $rate->delete();

        return redirect()->route('core.exchange-rates.index')
            ->with('success', 'Exchange rate deleted successfully.');
    }
}
