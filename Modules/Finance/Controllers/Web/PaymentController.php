<?php

namespace Modules\Finance\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Models\SupplierPayment;
use Modules\Finance\Services\PaymentService;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    public function index(Request $request)
    {
        $payments = SupplierPayment::with('bankAccount')
            ->orderBy('payment_date', 'desc')
            ->paginate(20);

        return view('finance.payments.index', compact('payments'));
    }

    public function create()
    {
        return view('finance.payments.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:CASH,BANK_TRANSFER,CHECK',
            'supplier_id' => 'required|exists:suppliers,id',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'company_id' => 'required|exists:companies,id',
        ]);

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
