<?php

namespace Modules\Finance\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Enums\AccountPurpose;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\AccountMapping;

/**
 * Account determination: which account each automatic posting purpose uses in the active company.
 */
class AccountMappingController extends Controller
{
    public function index(): View
    {
        return view('finance.account-mappings.index', [
            'purposes' => AccountPurpose::cases(),
            'mappings' => AccountMapping::pluck('account_id', 'purpose'),
            'accounts' => Account::where('is_postable', true)->where('status', 'active')->orderBy('account_code')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $companyId = $this->getActiveCompanyId();

        $validated = $request->validate([
            'mappings' => 'array',
            'mappings.*' => [
                'nullable', 'integer',
                Rule::exists('accounts', 'id')->where('company_id', $companyId)->where('is_postable', true)->whereNull('deleted_at'),
            ],
        ]);

        foreach (AccountPurpose::cases() as $purpose) {
            $accountId = $validated['mappings'][$purpose->value] ?? null;

            $accountId
                ? AccountMapping::updateOrCreate(['company_id' => $companyId, 'purpose' => $purpose->value], ['account_id' => $accountId])
                : AccountMapping::where('purpose', $purpose->value)->delete();
        }

        return redirect()->route('finance.account-mappings.index')->with('success', 'Account determination saved.');
    }
}
