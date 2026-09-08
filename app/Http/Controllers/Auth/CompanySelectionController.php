<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Core\Services\CompanyContextService;

class CompanySelectionController extends Controller
{
    public function __construct(protected CompanyContextService $companyContext)
    {
    }

    public function index()
    {
        $companies = $this->companyContext->getUserCompanies();

        if ($companies->count() === 0) {
            return redirect()->route('login')->with('error', 'You do not have access to any companies.');
        }

        if ($companies->count() === 1) {
            $this->companyContext->setActiveCompany($companies->first()->id);

            return redirect()->intended(route('dashboard', absolute: false));
        }

        return view('auth.company-selection', compact('companies'));
    }

    public function select(Request $request): RedirectResponse
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $companyId = (int) $request->input('company_id');

        if (!$this->companyContext->hasCompanyAccess($companyId)) {
            return redirect()->route('login')->with('error', 'You do not have access to the selected company.');
        }

        $this->companyContext->setActiveCompany($companyId);

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
