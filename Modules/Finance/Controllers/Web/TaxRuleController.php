<?php

namespace Modules\Finance\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Core\Support\Countries;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Models\Tax;
use Modules\Finance\Models\TaxRule;

/**
 * Tax determination rules: the tax code (and reverse charge) applied to invoice lines without one.
 */
class TaxRuleController extends Controller
{
    public function index(): View
    {
        return view('finance.tax-rules.index', [
            'rules' => TaxRule::with('tax')->orderBy('direction')->orderBy('priority')->get(),
            'taxes' => Tax::where('status', 'active')->orderBy('tax_code')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = $this->getActiveCompanyId();

        $validated = $request->validate([
            'direction' => ['required', Rule::in([TaxRule::DIRECTION_SALES, TaxRule::DIRECTION_PURCHASE])],
            'counterparty_country' => ['nullable', Rule::in(Countries::codes())],
            'counterparty_type' => 'required|in:any,b2b,b2c',
            'supply_type' => 'required|in:any,goods,services',
            'tax_id' => ['nullable', Rule::exists('taxes', 'id')->where('company_id', $companyId)],
            'reverse_charge' => 'boolean',
            'priority' => 'required|integer|min:1|max:999',
        ]);

        TaxRule::create([...$validated, 'company_id' => $companyId]);

        return redirect()->route('finance.tax-rules.index')->with('success', 'Tax rule added.');
    }

    public function destroy(int $id): RedirectResponse
    {
        TaxRule::findOrFail($id)->delete();

        return redirect()->route('finance.tax-rules.index')->with('success', 'Tax rule removed.');
    }
}
