<?php

namespace Modules\Finance\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Core\Exceptions\ClosedPeriodException;
use Modules\Core\Exceptions\InvalidAccountingTransactionException;
use Modules\Core\Exceptions\UnauthorizedCompanyAccessException;
use Modules\Core\Models\Company;
use Modules\Core\Models\Currency;
use Modules\Finance\Controllers\Controller;
use Modules\Finance\Exceptions\MissingAccountMappingException;
use Modules\Finance\Exceptions\MissingExchangeRateException;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\IntercompanyTransaction;
use Modules\Finance\Services\IntercompanyService;

class IntercompanyController extends Controller
{
    public function index(): View
    {
        $companyId = $this->getActiveCompanyId();

        return view('finance.intercompany.index', [
            'transactions' => IntercompanyTransaction::with(['sourceCompany', 'targetCompany', 'currency'])
                ->where(fn ($query) => $query->where('source_company_id', $companyId)->orWhere('target_company_id', $companyId))
                ->orderByDesc('transaction_date')->orderByDesc('id')->paginate(25),
            'counterparties' => Company::whereIn('id', $this->permissionService->companyIdsWithPermission(IntercompanyService::PERMISSION))
                ->whereKeyNot($companyId)->orderBy('name')->get(),
            'currencies' => Currency::where('status', 'active')->orderBy('code')->get(),
            'accounts' => Account::where('is_postable', true)->whereIn('account_type', ['REVENUE', 'EXPENSE'])->orderBy('account_code')->get(),
        ]);
    }

    public function store(Request $request, IntercompanyService $intercompany): RedirectResponse
    {
        $validated = $request->validate([
            'target_company_id' => 'required|integer|exists:companies,id',
            'transaction_date' => 'required|date',
            'currency_id' => 'required|exists:currencies,id',
            'amount' => 'required|numeric|gt:0',
            'description' => 'required|string|max:255',
            'source_account_id' => ['required', Rule::exists('accounts', 'id')->where('company_id', $this->getActiveCompanyId())->where('is_postable', 1)],
            'target_account_code' => 'required|string|max:50',
        ]);

        try {
            $intercompany->charge($this->companyContext->getActiveCompany(), Company::findOrFail($validated['target_company_id']), $validated);
        } catch (InvalidAccountingTransactionException|UnauthorizedCompanyAccessException|MissingAccountMappingException|MissingExchangeRateException|ClosedPeriodException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return redirect()->route('finance.intercompany.index')->with('success', 'Intercompany charge posted in both companies.');
    }
}
