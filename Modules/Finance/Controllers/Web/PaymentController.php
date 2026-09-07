<?php

namespace Modules\Finance\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Models\Payment;
use Modules\Finance\Services\PaymentService;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    public function index(Request $request)
    {
        $payments = Payment::with('paymentAccount')
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
            'payment_type' => 'required|in:CASH,BANK_TRANSFER,CHECK',
            'payment_account_id' => 'required|exists:finance_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'payee_type' => 'required|in:SUPPLIER,CUSTOMER,OTHER',
            'payee_name' => 'required|string|max:255',
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
        $payment = Payment::with('paymentAccount')->findOrFail($id);

        return view('finance.payments.show', compact('payment'));
    }
}
