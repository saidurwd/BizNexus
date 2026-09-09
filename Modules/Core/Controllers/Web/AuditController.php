<?php

namespace Modules\Core\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Models\AuditLog;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user', 'company');

        if ($request->filled('module')) {
            $query->byModule($request->get('module'));
        }

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->get('entity_type'));
        }

        if ($request->filled('action')) {
            $query->byAction($request->get('action'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->get('user_id'));
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->get('date_from') . ' 00:00:00');
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->get('date_to') . ' 23:59:59');
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('entity_type', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }

        $auditLogs = $query->orderByDesc('created_at')->paginate(25);

        $modules = AuditLog::select('module')->distinct()->orderBy('module')->pluck('module');
        $entityTypes = AuditLog::select('entity_type')->distinct()->orderBy('entity_type')->pluck('entity_type');
        $actions = AuditLog::select('action')->distinct()->orderBy('action')->pluck('action');
        $users = \App\Models\User::orderBy('name')->get(['id', 'name', 'email']);

        return view('core.audit.index', compact('auditLogs', 'modules', 'entityTypes', 'actions', 'users'));
    }

    public function show(int $id)
    {
        $auditLog = AuditLog::with('user', 'company')->findOrFail($id);

        $changes = [];

        if ($auditLog->old_values && $auditLog->new_values) {
            $allKeys = array_unique(array_merge(array_keys($auditLog->old_values), array_keys($auditLog->new_values)));
            foreach ($allKeys as $key) {
                $old = $auditLog->old_values[$key] ?? null;
                $new = $auditLog->new_values[$key] ?? null;
                if ($old !== $new) {
                    $changes[] = [
                        'field' => $this->humanizeField($key),
                        'old' => $this->formatValue($old),
                        'new' => $this->formatValue($new),
                    ];
                }
            }
        } elseif ($auditLog->new_values) {
            foreach ($auditLog->new_values as $key => $value) {
                $changes[] = [
                    'field' => $this->humanizeField($key),
                    'old' => null,
                    'new' => $this->formatValue($value),
                ];
            }
        } elseif ($auditLog->old_values) {
            foreach ($auditLog->old_values as $key => $value) {
                $changes[] = [
                    'field' => $this->humanizeField($key),
                    'old' => $this->formatValue($value),
                    'new' => null,
                ];
            }
        }

        return view('core.audit.show', compact('auditLog', 'changes'));
    }

    private function humanizeField(string $key): string
    {
        $replacements = [
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'deleted_at' => 'Deleted At',
            'company_id' => 'Company',
            'user_id' => 'User',
            'created_by' => 'Created By',
            'updated_by' => 'Updated By',
            'fiscal_year_id' => 'Fiscal Year',
            'fiscal_period_id' => 'Fiscal Period',
            'account_id' => 'Account',
            'journal_id' => 'Journal',
            'supplier_id' => 'Supplier',
            'customer_id' => 'Customer',
            'cost_center_id' => 'Cost Center',
            'bank_account_id' => 'Bank Account',
            'cash_account_id' => 'Cash Account',
            'tax_id' => 'Tax',
            'budget_id' => 'Budget',
            'currency_id' => 'Currency',
            'branch_id' => 'Branch',
            'department_id' => 'Department',
            'payment_method' => 'Payment Method',
            'invoice_number' => 'Invoice Number',
            'journal_number' => 'Journal Number',
            'reference_number' => 'Reference Number',
            'invoice_date' => 'Invoice Date',
            'due_date' => 'Due Date',
            'payment_date' => 'Payment Date',
            'journal_date' => 'Journal Date',
            'posting_date' => 'Posting Date',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'subtotal' => 'Subtotal',
            'tax_amount' => 'Tax Amount',
            'total_amount' => 'Total Amount',
            'budget_amount' => 'Budget Amount',
            'debit' => 'Debit',
            'credit' => 'Credit',
            'status' => 'Status',
            'account_code' => 'Account Code',
            'account_name' => 'Account Name',
            'account_type' => 'Account Type',
            'normal_balance' => 'Normal Balance',
            'is_active' => 'Active',
            'is_default' => 'Default',
            'description' => 'Description',
            'name' => 'Name',
            'code' => 'Code',
            'email' => 'Email',
            'phone' => 'Phone',
            'address' => 'Address',
            'amount' => 'Amount',
            'rate' => 'Rate',
            'exchange_rate' => 'Exchange Rate',
            'period' => 'Period',
            'period_number' => 'Period Number',
            'period_name' => 'Period Name',
        ];

        if (isset($replacements[$key])) {
            return $replacements[$key];
        }

        return ucfirst(str_replace('_', ' ', $key));
    }

    private function formatValue($value): string
    {
        if (is_null($value)) {
            return '<span class="text-muted">NULL</span>';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return '<pre class="mb-0">' . e(json_encode($value, JSON_PRETTY_PRINT)) . '</pre>';
        }

        if (is_numeric($value) && strval($value) === strval(number_format($value, 2))) {
            return number_format((float) $value, 2);
        }

        return e($value);
    }
}
