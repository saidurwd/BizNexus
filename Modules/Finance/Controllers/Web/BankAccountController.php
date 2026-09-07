<?php

namespace Modules\Finance\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Models\BankAccount;

class BankAccountController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->get('company_id', 1);

        $bankAccounts = BankAccount::with(['currency', 'glAccount'])
            ->where('company_id', $companyId)
            ->orderBy('bank_name')
            ->orderBy('account_name')
            ->get();

        return view('finance.bank-accounts.index', compact('bankAccounts'));
    }
}
