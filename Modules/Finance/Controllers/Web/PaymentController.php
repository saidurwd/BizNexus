<?php

namespace Modules\Finance\Controllers\Web;

use Modules\Finance\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Models\SupplierPayment;
use Modules\Finance\Services\PaymentService;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService,
        CompanyContextService $companyContext,
        PermissionService $permissionService
    ) {
        parent::__construct($companyContext, $permissionService);
    }

    public function index(Request $request)
    {
        $this->checkPermission('finance.suppliers.view');

        $payments = SupplierPayment::with('bankAccount')
            ->orderBy('payment_date', 'desc')
            ->paginate(20);

        return view('finance.payments.index', compact('payments'));
    }

    public function create()
    {
        $this->checkPermission('finance.suppliers.create');

        return view('finance.payments.create');
    }

    public function store(Request $request)
    {
        $this->checkPermission('finance.suppliers.create');

        $validated = $request->validate([
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:CASH,BANK_TRANSFER,CHECK',
            'supplier_id' => 'required|exists:suppliers,id',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:100',
            'description' => 'nullable|string',        ]);

        $validated['company_id'] = $this->getActiveCompanyId();

        $payment = $this->paymentService->createPayment($validated);

        return redirect()
            ->route('finance.payments.show', $payment->id)
            ->with('success', 'Payment created successfully');
    }

    public function show(string $id)
    {
        $payment = SupplierPayment::with('bankAccount')->findOrFail($id);

        return view('finance.payments.show', compact('payment'));
    }
}
