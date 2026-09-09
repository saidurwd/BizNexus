<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Modules\Finance\Controllers\DashboardController;
use Modules\Finance\Controllers\Web\AccountController;
use Modules\Finance\Controllers\Web\JournalController;
use Modules\Finance\Controllers\Web\SupplierController;
use Modules\Finance\Controllers\Web\CustomerController;
use Modules\Finance\Controllers\Web\ReportController;
use Modules\Finance\Controllers\Web\PaymentController;
use Modules\Finance\Controllers\Web\ReceiptController;
use Modules\Finance\Controllers\Web\SupplierInvoiceController;
use Modules\Finance\Controllers\Web\SupplierInvoiceLineController;
use Modules\Finance\Controllers\Web\SupplierCreditNoteController;
use Modules\Finance\Controllers\Web\SupplierDebitNoteController;
use Modules\Finance\Controllers\Web\CustomerInvoiceController;
use Modules\Finance\Controllers\Web\TaxController;
use Modules\Finance\Controllers\Web\BudgetController;
use Modules\Finance\Controllers\Web\BudgetLineController;
use Modules\Finance\Controllers\Web\CostCenterController;
use Modules\Finance\Controllers\Web\CashAccountController;
use Modules\Finance\Controllers\Web\BankAccountController;
use Modules\Finance\Controllers\Web\BankPaymentController;
use Modules\Finance\Controllers\Web\BankReceiptController;
use Modules\Finance\Controllers\Web\BankReconciliationController;
use Modules\Finance\Controllers\Web\RecurringJournalController;
use Modules\Finance\Controllers\Web\SupplierStatementController;
use Modules\Finance\Controllers\Web\CustomerStatementController;
use Modules\Core\Controllers\Web\CompanyController;
use Modules\Core\Controllers\Web\BranchController;
use Modules\Core\Controllers\Web\DepartmentController;
use Modules\Core\Controllers\Web\UserController;
use Modules\Core\Controllers\Web\RoleController;
use Modules\Core\Controllers\Web\ExchangeRateController;
use Modules\Core\Controllers\Web\PeriodClosingController;
use Modules\Core\Controllers\Web\FiscalYearController;
use Modules\Core\Controllers\Web\PermissionController;
use Modules\Core\Controllers\Web\AuditController;
use Modules\Core\Controllers\Web\NotificationController;
use Modules\Workflow\Controllers\Web\WorkflowController;

Route::get('/', function () {
    return redirect('/login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/companies', [CompanyController::class, 'index'])->name('core.companies.index');
    Route::get('/companies/create', [CompanyController::class, 'create'])->name('core.companies.create');
    Route::post('/companies', [CompanyController::class, 'store'])->name('core.companies.store');
    Route::get('/companies/{id}/edit', [CompanyController::class, 'edit'])->name('core.companies.edit');
    Route::put('/companies/{id}', [CompanyController::class, 'update'])->name('core.companies.update');
    Route::delete('/companies/{id}', [CompanyController::class, 'destroy'])->name('core.companies.destroy');

    // Users
    Route::get('/users', [UserController::class, 'index'])->name('core.users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('core.users.create');
    Route::post('/users', [UserController::class, 'store'])->name('core.users.store');
    Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('core.users.edit');
    Route::put('/users/{id}', [UserController::class, 'update'])->name('core.users.update');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('core.users.destroy');

    // Roles
    Route::get('/roles', [RoleController::class, 'index'])->name('core.roles.index');
    Route::get('/roles/create', [RoleController::class, 'create'])->name('core.roles.create');
    Route::post('/roles', [RoleController::class, 'store'])->name('core.roles.store');
    Route::get('/roles/{id}/edit', [RoleController::class, 'edit'])->name('core.roles.edit');
    Route::put('/roles/{id}', [RoleController::class, 'update'])->name('core.roles.update');
    Route::delete('/roles/{id}', [RoleController::class, 'destroy'])->name('core.roles.destroy');

    // Permissions
    Route::get('/permissions', [PermissionController::class, 'index'])->name('core.permissions.index');
    Route::get('/permissions/create', [PermissionController::class, 'create'])->name('core.permissions.create');
    Route::post('/permissions', [PermissionController::class, 'store'])->name('core.permissions.store');
    Route::get('/permissions/{id}/edit', [PermissionController::class, 'edit'])->name('core.permissions.edit');
    Route::put('/permissions/{id}', [PermissionController::class, 'update'])->name('core.permissions.update');
    Route::delete('/permissions/{id}', [PermissionController::class, 'destroy'])->name('core.permissions.destroy');

    // Audit
    Route::get('/audit', [AuditController::class, 'index'])->name('core.audit.index');
    Route::get('/audit/{id}', [AuditController::class, 'show'])->name('core.audit.show');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('core.notifications.index');
    Route::get('/notifications/{id}', [NotificationController::class, 'show'])->name('core.notifications.show');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('core.notifications.mark-read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('core.notifications.mark-all-read');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('core.notifications.destroy');
    Route::delete('/notifications', [NotificationController::class, 'destroyAll'])->name('core.notifications.destroy-all');

    // Workflow
    Route::get('/workflows', [WorkflowController::class, 'index'])->name('workflow.index');
    Route::get('/workflows/{id}', [WorkflowController::class, 'show'])->name('workflow.show');
    Route::get('/workflows/definitions', [WorkflowController::class, 'definitions'])->name('workflow.definitions');
    Route::post('/workflows/definitions', [WorkflowController::class, 'storeDefinition'])->name('workflow.definitions.store');
    Route::put('/workflows/definitions/{id}', [WorkflowController::class, 'updateDefinition'])->name('workflow.definitions.update');

    Route::get('/exchange-rates', [ExchangeRateController::class, 'index'])->name('core.exchange-rates.index');
    Route::get('/exchange-rates/create', [ExchangeRateController::class, 'create'])->name('core.exchange-rates.create');
    Route::post('/exchange-rates', [ExchangeRateController::class, 'store'])->name('core.exchange-rates.store');
    Route::get('/exchange-rates/{id}/edit', [ExchangeRateController::class, 'edit'])->name('core.exchange-rates.edit');
    Route::put('/exchange-rates/{id}', [ExchangeRateController::class, 'update'])->name('core.exchange-rates.update');
    Route::delete('/exchange-rates/{id}', [ExchangeRateController::class, 'destroy'])->name('core.exchange-rates.destroy');
    Route::get('/periods', [PeriodClosingController::class, 'index'])->name('core.periods.index');
    Route::get('/fiscal-years/create', [FiscalYearController::class, 'create'])->name('core.fiscal-years.create');
    Route::post('/fiscal-years', [FiscalYearController::class, 'store'])->name('core.fiscal-years.store');

    Route::post('/periods/{id}/close', [PeriodClosingController::class, 'closePeriod'])->name('core.periods.close');
    Route::post('/periods/{id}/reopen', [PeriodClosingController::class, 'reopenPeriod'])->name('core.periods.reopen');
    Route::post('/periods/{id}/lock', [PeriodClosingController::class, 'lockPeriod'])->name('core.periods.lock');
    Route::post('/periods/{id}/validate', [PeriodClosingController::class, 'validatePeriod'])->name('core.periods.validate');



    Route::get('/branches', [BranchController::class, 'index'])->name('core.branches.index');
    Route::get('/branches/create', [BranchController::class, 'create'])->name('core.branches.create');
    Route::post('/branches', [BranchController::class, 'store'])->name('core.branches.store');
    Route::get('/branches/{id}/edit', [BranchController::class, 'edit'])->name('core.branches.edit');
    Route::put('/branches/{id}', [BranchController::class, 'update'])->name('core.branches.update');
    Route::delete('/branches/{id}', [BranchController::class, 'destroy'])->name('core.branches.destroy');

    Route::get('/departments', [DepartmentController::class, 'index'])->name('core.departments.index');
    Route::get('/departments/create', [DepartmentController::class, 'create'])->name('core.departments.create');
    Route::post('/departments', [DepartmentController::class, 'store'])->name('core.departments.store');
    Route::get('/departments/{id}/edit', [DepartmentController::class, 'edit'])->name('core.departments.edit');
    Route::put('/departments/{id}', [DepartmentController::class, 'update'])->name('core.departments.update');
    Route::delete('/departments/{id}', [DepartmentController::class, 'destroy'])->name('core.departments.destroy');

    // Main Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Finance Module
    Route::prefix('finance')->name('finance.')->group(function () {

        // Finance Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Chart of Accounts
        Route::get('/accounts', [AccountController::class, 'index'])->name('accounts.index');
        Route::get('/accounts/create', [AccountController::class, 'create'])->name('accounts.create');
        Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
        Route::get('/accounts/{id}', [AccountController::class, 'show'])->name('accounts.show');
        Route::get('/accounts/{id}/edit', [AccountController::class, 'edit'])->name('accounts.edit');
        Route::put('/accounts/{id}', [AccountController::class, 'update'])->name('accounts.update');
        Route::delete('/accounts/{id}', [AccountController::class, 'destroy'])->name('accounts.destroy');

        // Journals
        Route::get('/journals', [JournalController::class, 'index'])->name('journals.index');
        Route::get('/journals/create', [JournalController::class, 'create'])->name('journals.create');
        Route::post('/journals', [JournalController::class, 'store'])->name('journals.store');
        Route::get('/journals/{id}', [JournalController::class, 'show'])->name('journals.show');
        Route::delete('/journals/{id}', [JournalController::class, 'destroy'])->name('journals.destroy');
        Route::post('/journals/{id}/submit', [JournalController::class, 'submit'])->name('journals.submit');
        Route::post('/journals/{id}/approve', [JournalController::class, 'approve'])->name('journals.approve');
        Route::post('/journals/{id}/reject', [JournalController::class, 'reject'])->name('journals.reject');
        Route::post('/journals/{id}/post', [JournalController::class, 'post'])->name('journals.post');
        Route::post('/journals/{id}/reverse', [JournalController::class, 'reverse'])->name('journals.reverse');
        Route::post('/journals/{id}/cancel', [JournalController::class, 'cancel'])->name('journals.cancel');

        // General Ledger
        Route::get('/general-ledger', [JournalController::class, 'generalLedger'])->name('general-ledger');

        // Accounts Payable
        Route::get('/ap-aging', [ReportController::class, 'apReport'])->name('ap-aging');

        // Accounts Receivable
        Route::get('/ar-aging', [ReportController::class, 'arReport'])->name('ar-aging');

        // Cash & Bank
        Route::get('/bank-accounts', [BankAccountController::class, 'index'])->name('bank-accounts.index');
        Route::get('/bank-receipts', [ReceiptController::class, 'bankReceipts'])->name('bank-receipts.index');

        // Suppliers
        Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::get('/suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
        Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::get('/suppliers/{id}', [SupplierController::class, 'show'])->name('suppliers.show');
        Route::get('/suppliers/{id}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
        Route::put('/suppliers/{id}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');

        // Customers
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('/customers/{id}', [CustomerController::class, 'show'])->name('customers.show');

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/general-ledger', [ReportController::class, 'generalLedger'])->name('general-ledger');
            Route::get('/trial-balance', [ReportController::class, 'trialBalance'])->name('trial-balance');
            Route::get('/profit-loss', [ReportController::class, 'profitLoss'])->name('profit-loss');
            Route::get('/balance-sheet', [ReportController::class, 'balanceSheet'])->name('balance-sheet');
            Route::get('/cash-flow', [ReportController::class, 'cashFlow'])->name('cash-flow');
            Route::get('/ap', [ReportController::class, 'apReport'])->name('ap');
            Route::get('/ar', [ReportController::class, 'arReport'])->name('ar');
            Route::get('/payment-register', [ReportController::class, 'paymentRegister'])->name('payment-register');
            Route::get('/receipt-register', [ReportController::class, 'receiptRegister'])->name('receipt-register');
            Route::get('/cash-book', [ReportController::class, 'cashBook'])->name('cash-book');
            Route::get('/bank-book', [ReportController::class, 'bankBook'])->name('bank-book');
            Route::get('/management', [ReportController::class, 'management'])->name('management');
            Route::post('/generate-async', [ReportController::class, 'generateAsync'])->name('generate-async');
        });

        // Payments
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/payments/create', [PaymentController::class, 'create'])->name('payments.create');
        Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
        Route::get('/payments/{id}', [PaymentController::class, 'show'])->name('payments.show');
        Route::post('/payments/{id}/submit', [PaymentController::class, 'submit'])->name('payments.submit');
        Route::post('/payments/{id}/approve', [PaymentController::class, 'approve'])->name('payments.approve');
        Route::post('/payments/{id}/reject', [PaymentController::class, 'reject'])->name('payments.reject');
        Route::post('/payments/{id}/cancel', [PaymentController::class, 'cancel'])->name('payments.cancel');

        // Receipts
        Route::get('/receipts', [ReceiptController::class, 'index'])->name('receipts.index');
        Route::get('/receipts/create', [ReceiptController::class, 'create'])->name('receipts.create');
        Route::post('/receipts', [ReceiptController::class, 'store'])->name('receipts.store');
        Route::get('/receipts/{id}', [ReceiptController::class, 'show'])->name('receipts.show');
        Route::post('/receipts/{id}/submit', [ReceiptController::class, 'submit'])->name('receipts.submit');
        Route::post('/receipts/{id}/approve', [ReceiptController::class, 'approve'])->name('receipts.approve');
        Route::post('/receipts/{id}/reject', [ReceiptController::class, 'reject'])->name('receipts.reject');
        Route::post('/receipts/{id}/cancel', [ReceiptController::class, 'cancel'])->name('receipts.cancel');

        // Supplier Invoices
        Route::get('/supplier-invoices', [SupplierInvoiceController::class, 'index'])->name('supplier-invoices.index');
        Route::get('/supplier-invoices/create', [SupplierInvoiceController::class, 'create'])->name('supplier-invoices.create');
        Route::post('/supplier-invoices', [SupplierInvoiceController::class, 'store'])->name('supplier-invoices.store');
        Route::get('/supplier-invoices/{id}', [SupplierInvoiceController::class, 'show'])->name('supplier-invoices.show');
        Route::get('/supplier-invoices/{id}/edit', [SupplierInvoiceController::class, 'edit'])->name('supplier-invoices.edit');
        Route::put('/supplier-invoices/{id}', [SupplierInvoiceController::class, 'update'])->name('supplier-invoices.update');
        Route::delete('/supplier-invoices/{id}', [SupplierInvoiceController::class, 'destroy'])->name('supplier-invoices.destroy');
        Route::post('/supplier-invoices/{id}/submit', [SupplierInvoiceController::class, 'submit'])->name('supplier-invoices.submit');
        Route::post('/supplier-invoices/{id}/approve', [SupplierInvoiceController::class, 'approve'])->name('supplier-invoices.approve');
        Route::post('/supplier-invoices/{id}/reject', [SupplierInvoiceController::class, 'reject'])->name('supplier-invoices.reject');
        Route::post('/supplier-invoices/{id}/post', [SupplierInvoiceController::class, 'post'])->name('supplier-invoices.post');
        Route::post('/supplier-invoices/{id}/cancel', [SupplierInvoiceController::class, 'cancel'])->name('supplier-invoices.cancel');
        Route::post('/supplier-invoices/{invoiceId}/lines', [SupplierInvoiceLineController::class, 'store'])->name('supplier-invoices.lines.store');
        Route::put('/supplier-invoices/{invoiceId}/lines/{lineId}', [SupplierInvoiceLineController::class, 'update'])->name('supplier-invoices.lines.update');
        Route::delete('/supplier-invoices/{invoiceId}/lines/{lineId}', [SupplierInvoiceLineController::class, 'destroy'])->name('supplier-invoices.lines.destroy');

        // Supplier Credit Notes
        Route::get('/supplier-credit-notes', [SupplierCreditNoteController::class, 'index'])->name('supplier-credit-notes.index');
        Route::get('/supplier-credit-notes/create', [SupplierCreditNoteController::class, 'create'])->name('supplier-credit-notes.create');
        Route::post('/supplier-credit-notes', [SupplierCreditNoteController::class, 'store'])->name('supplier-credit-notes.store');
        Route::get('/supplier-credit-notes/{id}', [SupplierCreditNoteController::class, 'show'])->name('supplier-credit-notes.show');
        Route::get('/supplier-credit-notes/{id}/edit', [SupplierCreditNoteController::class, 'edit'])->name('supplier-credit-notes.edit');
        Route::put('/supplier-credit-notes/{id}', [SupplierCreditNoteController::class, 'update'])->name('supplier-credit-notes.update');
        Route::delete('/supplier-credit-notes/{id}', [SupplierCreditNoteController::class, 'destroy'])->name('supplier-credit-notes.destroy');
        Route::post('/supplier-credit-notes/{id}/submit', [SupplierCreditNoteController::class, 'submit'])->name('supplier-credit-notes.submit');
        Route::post('/supplier-credit-notes/{id}/approve', [SupplierCreditNoteController::class, 'approve'])->name('supplier-credit-notes.approve');
        Route::post('/supplier-credit-notes/{id}/post', [SupplierCreditNoteController::class, 'post'])->name('supplier-credit-notes.post');
        Route::post('/supplier-credit-notes/{id}/cancel', [SupplierCreditNoteController::class, 'cancel'])->name('supplier-credit-notes.cancel');

        // Supplier Debit Notes
        Route::get('/supplier-debit-notes', [SupplierDebitNoteController::class, 'index'])->name('supplier-debit-notes.index');
        Route::get('/supplier-debit-notes/create', [SupplierDebitNoteController::class, 'create'])->name('supplier-debit-notes.create');
        Route::post('/supplier-debit-notes', [SupplierDebitNoteController::class, 'store'])->name('supplier-debit-notes.store');
        Route::get('/supplier-debit-notes/{id}', [SupplierDebitNoteController::class, 'show'])->name('supplier-debit-notes.show');
        Route::get('/supplier-debit-notes/{id}/edit', [SupplierDebitNoteController::class, 'edit'])->name('supplier-debit-notes.edit');
        Route::put('/supplier-debit-notes/{id}', [SupplierDebitNoteController::class, 'update'])->name('supplier-debit-notes.update');
        Route::delete('/supplier-debit-notes/{id}', [SupplierDebitNoteController::class, 'destroy'])->name('supplier-debit-notes.destroy');
        Route::post('/supplier-debit-notes/{id}/post', [SupplierDebitNoteController::class, 'post'])->name('supplier-debit-notes.post');
        Route::post('/supplier-debit-notes/{id}/cancel', [SupplierDebitNoteController::class, 'cancel'])->name('supplier-debit-notes.cancel');

        // Supplier Statements
        Route::get('/supplier-statements', [SupplierStatementController::class, 'index'])->name('supplier-statements.index');
        Route::get('/supplier-statements/{id}', [SupplierStatementController::class, 'show'])->name('supplier-statements.show');

        // Customer Invoices
        Route::get('/customer-invoices', [CustomerInvoiceController::class, 'index'])->name('customer-invoices.index');
        Route::get('/customer-invoices/create', [CustomerInvoiceController::class, 'create'])->name('customer-invoices.create');
        Route::post('/customer-invoices', [CustomerInvoiceController::class, 'store'])->name('customer-invoices.store');
        Route::get('/customer-invoices/{id}', [CustomerInvoiceController::class, 'show'])->name('customer-invoices.show');
        Route::get('/customer-invoices/{id}/edit', [CustomerInvoiceController::class, 'edit'])->name('customer-invoices.edit');
        Route::put('/customer-invoices/{id}', [CustomerInvoiceController::class, 'update'])->name('customer-invoices.update');
        Route::delete('/customer-invoices/{id}', [CustomerInvoiceController::class, 'destroy'])->name('customer-invoices.destroy');
        Route::post('/customer-invoices/{id}/submit', [CustomerInvoiceController::class, 'submit'])->name('customer-invoices.submit');
        Route::post('/customer-invoices/{id}/approve', [CustomerInvoiceController::class, 'approve'])->name('customer-invoices.approve');
        Route::post('/customer-invoices/{id}/reject', [CustomerInvoiceController::class, 'reject'])->name('customer-invoices.reject');
        Route::post('/customer-invoices/{id}/post', [CustomerInvoiceController::class, 'post'])->name('customer-invoices.post');
        Route::post('/customer-invoices/{id}/cancel', [CustomerInvoiceController::class, 'cancel'])->name('customer-invoices.cancel');

        // Customer Statements
        Route::get('/customer-statements', [CustomerStatementController::class, 'index'])->name('customer-statements.index');
        Route::get('/customer-statements/{id}', [CustomerStatementController::class, 'show'])->name('customer-statements.show');

        // Taxes
        Route::get('/taxes', [TaxController::class, 'index'])->name('taxes.index');
        Route::get('/taxes/create', [TaxController::class, 'create'])->name('taxes.create');
        Route::post('/taxes', [TaxController::class, 'store'])->name('taxes.store');
        Route::get('/taxes/{id}', [TaxController::class, 'show'])->name('taxes.show');
        Route::get('/taxes/{id}/edit', [TaxController::class, 'edit'])->name('taxes.edit');
        Route::put('/taxes/{id}', [TaxController::class, 'update'])->name('taxes.update');
        Route::delete('/taxes/{id}', [TaxController::class, 'destroy'])->name('taxes.destroy');

        // Cash Accounts
        Route::get('/cash-accounts', [CashAccountController::class, 'index'])->name('cash-accounts.index');
        Route::get('/cash-accounts/create', [CashAccountController::class, 'create'])->name('cash-accounts.create');
        Route::post('/cash-accounts', [CashAccountController::class, 'store'])->name('cash-accounts.store');
        Route::get('/cash-accounts/{id}/edit', [CashAccountController::class, 'edit'])->name('cash-accounts.edit');
        Route::put('/cash-accounts/{id}', [CashAccountController::class, 'update'])->name('cash-accounts.update');
        Route::delete('/cash-accounts/{id}', [CashAccountController::class, 'destroy'])->name('cash-accounts.destroy');

        // Bank Accounts
        Route::get('/bank-accounts', [BankAccountController::class, 'index'])->name('bank-accounts.index');
        Route::get('/bank-accounts/create', [BankAccountController::class, 'create'])->name('bank-accounts.create');
        Route::post('/bank-accounts', [BankAccountController::class, 'store'])->name('bank-accounts.store');
        Route::get('/bank-accounts/{id}/edit', [BankAccountController::class, 'edit'])->name('bank-accounts.edit');
        Route::put('/bank-accounts/{id}', [BankAccountController::class, 'update'])->name('bank-accounts.update');
        Route::delete('/bank-accounts/{id}', [BankAccountController::class, 'destroy'])->name('bank-accounts.destroy');

        // Bank Payments
        Route::get('/bank-payments', [BankPaymentController::class, 'index'])->name('bank-payments.index');
        Route::get('/bank-payments/create', [BankPaymentController::class, 'create'])->name('bank-payments.create');
        Route::post('/bank-payments', [BankPaymentController::class, 'store'])->name('bank-payments.store');

        // Bank Receipts
        Route::get('/bank-receipts', [BankReceiptController::class, 'index'])->name('bank-receipts.index');
        Route::get('/bank-receipts/create', [BankReceiptController::class, 'create'])->name('bank-receipts.create');
        Route::post('/bank-receipts', [BankReceiptController::class, 'store'])->name('bank-receipts.store');

        // Bank Reconciliation
        Route::get('/bank-reconciliation', [BankReconciliationController::class, 'index'])->name('bank-reconciliation.index');
        Route::get('/bank-reconciliation/create', [BankReconciliationController::class, 'create'])->name('bank-reconciliation.create');
        Route::post('/bank-reconciliation', [BankReconciliationController::class, 'store'])->name('bank-reconciliation.store');

        // Recurring Journals
        Route::get('/recurring-journals', [RecurringJournalController::class, 'index'])->name('recurring-journals.index');
        Route::get('/recurring-journals/create', [RecurringJournalController::class, 'create'])->name('recurring-journals.create');
        Route::post('/recurring-journals', [RecurringJournalController::class, 'store'])->name('recurring-journals.store');
        Route::get('/recurring-journals/{id}/edit', [RecurringJournalController::class, 'edit'])->name('recurring-journals.edit');
        Route::put('/recurring-journals/{id}', [RecurringJournalController::class, 'update'])->name('recurring-journals.update');
        Route::delete('/recurring-journals/{id}', [RecurringJournalController::class, 'destroy'])->name('recurring-journals.destroy');

        // Cost Centers
        Route::get('/cost-centers', [CostCenterController::class, 'index'])->name('cost-centers.index');
        Route::get('/cost-centers/create', [CostCenterController::class, 'create'])->name('cost-centers.create');
        Route::post('/cost-centers', [CostCenterController::class, 'store'])->name('cost-centers.store');
        Route::get('/cost-centers/{id}/edit', [CostCenterController::class, 'edit'])->name('cost-centers.edit');
        Route::put('/cost-centers/{id}', [CostCenterController::class, 'update'])->name('cost-centers.update');
        Route::delete('/cost-centers/{id}', [CostCenterController::class, 'destroy'])->name('cost-centers.destroy');

        // Budgets
        Route::get('/budgets', [BudgetController::class, 'index'])->name('budgets.index');
        Route::get('/budgets/create', [BudgetController::class, 'create'])->name('budgets.create');
        Route::post('/budgets', [BudgetController::class, 'store'])->name('budgets.store');
        Route::get('/budgets/{id}', [BudgetController::class, 'show'])->name('budgets.show');
        Route::get('/budgets/{id}/edit', [BudgetController::class, 'edit'])->name('budgets.edit');
        Route::put('/budgets/{id}', [BudgetController::class, 'update'])->name('budgets.update');
        Route::delete('/budgets/{id}', [BudgetController::class, 'destroy'])->name('budgets.destroy');
        Route::post('/budgets/{id}/submit', [BudgetController::class, 'submit'])->name('budgets.submit');
        Route::post('/budgets/{id}/approve', [BudgetController::class, 'approve'])->name('budgets.approve');
        Route::post('/budgets/{id}/reject', [BudgetController::class, 'reject'])->name('budgets.reject');
        Route::post('/budgets/{budgetId}/lines', [BudgetLineController::class, 'store'])->name('budgets.lines.store');
        Route::put('/budgets/{budgetId}/lines/{lineId}', [BudgetLineController::class, 'update'])->name('budgets.lines.update');
        Route::delete('/budgets/{budgetId}/lines/{lineId}', [BudgetLineController::class, 'destroy'])->name('budgets.lines.destroy');
        Route::get('/budgets/lines/accounts', [BudgetLineController::class, 'accounts'])->name('budgets.lines.accounts');
        Route::get('/budgets/lines/cost-centers', [BudgetLineController::class, 'costCenters'])->name('budgets.lines.cost-centers');

        // Budget vs Actual
        Route::get('/budget-vs-actual', [ReportController::class, 'budgetVsActual'])->name('budget-vs-actual');
    });

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
