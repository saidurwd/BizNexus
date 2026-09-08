<?php

namespace Modules\Finance\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Models\Supplier;

class SupplierStatementController extends Controller
{
    public function index(Request $request)
    {
        $suppliers = Supplier::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'supplier_code', 'name', 'email', 'phone']);

        return view('finance.supplier-statements.index', compact('suppliers'));
    }

    public function show(Request $request, int $id)
    {
        $supplier = Supplier::with(['invoices', 'payments'])->findOrFail($id);

        $startDate = $request->get('start_date') ? \Carbon\Carbon::parse($request->get('start_date')) : null;
        $endDate = $request->get('end_date') ? \Carbon\Carbon::parse($request->get('end_date')) : null;

        $invoices = $supplier->invoices()
            ->when($startDate, fn($q) => $q->where('invoice_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->where('invoice_date', '<=', $endDate))
            ->orderBy('invoice_date')
            ->get();

        $payments = $supplier->payments()
            ->when($startDate, fn($q) => $q->where('payment_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->where('payment_date', '<=', $endDate))
            ->orderBy('payment_date')
            ->get();

        $openingBalance = $this->calculateOpeningBalance($supplier, $startDate);

        $statement = $this->buildStatement($supplier, $invoices, $payments, $openingBalance);

        return view('finance.supplier-statements.show', [
            'supplier' => $supplier,
            'statement' => $statement,
            'startDate' => $startDate?->format('Y-m-d'),
            'endDate' => $endDate?->format('Y-m-d'),
        ]);
    }

    protected function calculateOpeningBalance(Supplier $supplier, ?\Carbon\Carbon $startDate): float
    {
        if (!$startDate) {
            return 0;
        }

        $invoices = $supplier->invoices()
            ->where('invoice_date', '<', $startDate)
            ->whereIn('status', ['approved', 'paid', 'submitted'])
            ->sum('total_amount');

        $payments = $supplier->payments()
            ->where('payment_date', '<', $startDate)
            ->whereIn('status', ['POSTED', 'PENDING'])
            ->sum('amount');

        return $invoices - $payments;
    }

    protected function buildStatement(Supplier $supplier, $invoices, $payments, float $openingBalance): array
    {
        $balance = $openingBalance;
        $entries = [];

        foreach ($invoices as $invoice) {
            $balance += $invoice->total_amount;
            $entries[] = [
                'date' => $invoice->invoice_date,
                'type' => 'invoice',
                'number' => $invoice->invoice_number,
                'description' => $invoice->description ?? 'Supplier Invoice',
                'debit' => $invoice->total_amount,
                'credit' => 0,
                'balance' => $balance,
            ];
        }

        foreach ($payments as $payment) {
            $balance -= $payment->amount;
            $entries[] = [
                'date' => $payment->payment_date,
                'type' => 'payment',
                'number' => $payment->payment_number ?? 'Payment',
                'description' => $payment->description ?? 'Supplier Payment',
                'debit' => 0,
                'credit' => $payment->amount,
                'balance' => $balance,
            ];
        }

        usort($entries, fn($a, $b) => $a['date'] <=> $b['date']);

        return [
            'opening_balance' => $openingBalance,
            'closing_balance' => $balance,
            'total_invoices' => $invoices->sum('total_amount'),
            'total_payments' => $payments->sum('amount'),
            'entries' => $entries,
        ];
    }
}
