<?php

namespace Modules\Finance\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Core\Support\Countries;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\Tax;

/**
 * Tax codes. The rate given on creation is the first effective rate; later changes are added as new
 * effective-dated rates so documents keep the rate in force on their own date.
 */
class TaxController extends Controller
{
    public function index(): View
    {
        $taxes = Tax::orderBy('tax_code')->get();

        return view('finance.taxes.index', compact('taxes'));
    }

    public function create(): View
    {
        return view('finance.taxes.create', ['accounts' => $this->postableAccounts()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            ...$this->rules(),
            'rate' => 'required|numeric|min:0|max:100',
            'effective_from' => 'nullable|date',
        ]);

        $tax = Tax::create(collect($validated)->except('effective_from')->all());
        $tax->rates()->update(['effective_from' => $validated['effective_from'] ?? '1900-01-01']);

        return redirect()->route('finance.taxes.show', $tax->id)->with('success', 'Tax code created successfully');
    }

    public function show(int $id): View
    {
        $tax = Tax::with(['rates' => fn ($query) => $query->orderByDesc('effective_from'), 'components', 'inputAccount', 'outputAccount'])->findOrFail($id);

        return view('finance.taxes.show', [
            'tax' => $tax,
            'availableComponents' => Tax::where('is_group', false)->whereKeyNot($tax->id)->orderBy('tax_code')->get(),
        ]);
    }

    public function edit(int $id): View
    {
        return view('finance.taxes.edit', ['tax' => Tax::findOrFail($id), 'accounts' => $this->postableAccounts()]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $tax = Tax::findOrFail($id);

        $tax->update($request->validate($this->rules($tax->id)));

        return redirect()->route('finance.taxes.show', $tax->id)->with('success', 'Tax updated successfully');
    }

    public function destroy(int $id): RedirectResponse
    {
        Tax::findOrFail($id)->delete();

        return redirect()->route('finance.taxes.index')->with('success', 'Tax deleted successfully');
    }

    public function storeRate(Request $request, int $id): RedirectResponse
    {
        $tax = Tax::findOrFail($id);

        $validated = $request->validate([
            'rate' => 'required|numeric|min:0|max:100',
            'effective_from' => ['required', 'date', Rule::unique('tax_rates', 'effective_from')->where('tax_id', $tax->id)],
        ]);

        $tax->rates()->whereNull('effective_to')->whereDate('effective_from', '<', $validated['effective_from'])
            ->update(['effective_to' => now()->parse($validated['effective_from'])->subDay()->toDateString()]);
        $tax->rates()->create($validated);
        $tax->update(['rate' => $tax->rateOn(now())]);

        return redirect()->route('finance.taxes.show', $tax->id)->with('success', 'Rate change recorded.');
    }

    public function storeComponent(Request $request, int $id): RedirectResponse
    {
        $group = Tax::where('is_group', true)->findOrFail($id);

        $validated = $request->validate([
            'component_tax_id' => ['required', Rule::exists('taxes', 'id')->where('company_id', $group->company_id)->where('is_group', false)],
            'sequence' => 'required|integer|min:1|max:99',
            'is_compound' => 'boolean',
        ]);

        $group->components()->syncWithoutDetaching([
            $validated['component_tax_id'] => ['sequence' => $validated['sequence'], 'is_compound' => $validated['is_compound'] ?? false],
        ]);

        return redirect()->route('finance.taxes.show', $group->id)->with('success', 'Component added.');
    }

    public function destroyComponent(int $id, int $componentId): RedirectResponse
    {
        Tax::where('is_group', true)->findOrFail($id)->components()->detach($componentId);

        return redirect()->route('finance.taxes.show', $id)->with('success', 'Component removed.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(?int $ignoreId = null): array
    {
        $companyId = $this->getActiveCompanyId();
        $postableAccount = Rule::exists('accounts', 'id')->where('company_id', $companyId)->where('is_postable', true);

        return [
            'tax_code' => ['required', 'string', 'max:20', Rule::unique('taxes', 'tax_code')->where('company_id', $companyId)->ignore($ignoreId)],
            'tax_name' => 'required|string|max:100',
            'tax_type' => ['required', Rule::in(Tax::TYPES)],
            'country_code' => ['nullable', Rule::in(Countries::codes())],
            'region_code' => 'nullable|string|max:10',
            'is_inclusive' => 'boolean',
            'is_group' => 'boolean',
            'is_recoverable' => 'boolean',
            'input_account_id' => ['nullable', $postableAccount],
            'output_account_id' => ['nullable', $postableAccount],
            'status' => 'required|in:active,inactive',
        ];
    }

    protected function postableAccounts()
    {
        return Account::where('is_postable', true)->where('status', 'active')->orderBy('account_code')->get(['id', 'account_code', 'account_name']);
    }
}
