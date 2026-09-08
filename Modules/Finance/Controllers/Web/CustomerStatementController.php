<?php

namespace Modules\Finance\Controllers\Web;

use Modules\Finance\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Models\Customer;
use Modules\Core\Services\CompanyContextService;
use Modules\Core\Services\PermissionService;

class CustomerStatementController extends Controller
{
    public function __construct(
        CompanyContextService $companyContext,
        PermissionService $permissionService
    ) {
        parent::__construct($companyContext, $permissionService);
    }

    public function index(Request $request)
    {
        $this->checkPermission('finance.customers.view');

        $customers = Customer::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'customer_code', 'name', 'email', 'phone']);

        return view('finance.customer-statements.index', compact('customers'));
    }

    public function show(Request $request, int $id)
    {
        $this->checkPermission('finance.customers.view');

        $customer = Customer::with(['invoices', 'receipts'])->findOrFail($id);

        $startDate = $request->get('start_date') ? \Carbon\Carbon::parse($request->get('start_date')) : null;
        $endDate = $request->get('end_date') ? \Carbon\Carbon::parse($request->get('end_date')) : null;

        $invoices = $customer->invoices()
            ->when($startDate, fn($q) => $q->where('invoice_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->where('invoice_date', '<=', $endDate))
            ->orderBy('invoice_date')
            ->get();

        $receipts = $customer->receipts()
            ->when($startDate, fn($q) => $q->where('receipt_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->where('receipt_date', '<=', $endDate))
            ->orderBy('receipt_date')
            ->get();

        $openingBalance = $this->calculateOpeningBalance($customer, $startDate);

        $statement = $this->buildStatement($customer, $invoices, $receipts, $openingBalance);

        return view('finance.customer-statements.show', [
            'customer' => $customer,
            'statement' => $statement,
            'startDate' => $startDate?->format('Y-m-d'),
            'endDate' => $endDate?->format('Y-m-d'),
        ]);
    }

    protected function calculateOpeningBalance(Customer $customer, ?\Carbon\Carbon $startDate): float
    {
        if (!$startDate) {
            return 0;
        }

        $invoices = $customer->invoices()
            ->where('invoice_date', '<', $startDate)
            ->whereIn('status', ['approved', 'posted', 'submitted'])
            ->sum('total_amount');

        $receipts = $customer->receipts()
            ->where('receipt_date', '<', $startDate)
            ->whereIn('status', ['POSTED', 'PENDING'])
            ->sum('amount');

        return $invoices - $receipts;
    }

    protected function buildStatement(Customer $customer, $invoices, $receipts, float $openingBalance): array
    {
        $balance = $openingBalance;
        $entries = [];

        foreach ($invoices as $invoice) {
            $balance += $invoice->total_amount;
            $entries[] = [
                'date' => $invoice->invoice_date,
                'type' => 'invoice',
                'number' => $invoice->invoice_number,
                'description' => $invoice->description ?? 'Customer Invoice',
                'debit' => $invoice->total_amount,
                'credit' => 0,
                'balance' => $balance,
            ];
        }

        foreach ($receipts as $receipt) {
            $balance -= $receipt->amount;
            $entries[] = [
                'date' => $receipt->receipt_date,
                'type' => 'receipt',
                'number' => $receipt->receipt_number ?? 'Receipt',
                'description' => $receipt->description ?? 'Customer Receipt',
                'debit' => 0,
                'credit' => $receipt->amount,
                'balance' => $balance,
            ];
        }

        usort($entries, fn($a, $b) => $a['date'] <=> $b['date']);

        return [
            'opening_balance' => $openingBalance,
            'closing_balance' => $balance,
            'total_invoices' => $invoices->sum('total_amount'),
            'total_receipts' => $receipts->sum('amount'),
            'entries' => $entries,
        ];
    }
}
