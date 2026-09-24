<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Modules\Core\Controllers\Web\AuditController;
use Modules\Core\Controllers\Web\BranchController;
use Modules\Core\Controllers\Web\CompanyController;
use Modules\Core\Controllers\Web\DepartmentController;
use Modules\Core\Controllers\Web\ExchangeRateController;
use Modules\Core\Controllers\Web\FiscalYearController;
use Modules\Core\Controllers\Web\NotificationController;
use Modules\Core\Controllers\Web\PeriodClosingController;
use Modules\Core\Controllers\Web\PermissionController;
use Modules\Core\Controllers\Web\RoleController;
use Modules\Core\Controllers\Web\UserController;
use Modules\Finance\Controllers\DashboardController;
use Modules\Finance\Controllers\Web\AccountController;
use Modules\Finance\Controllers\Web\BankAccountController;
use Modules\Finance\Controllers\Web\BankPaymentController;
use Modules\Finance\Controllers\Web\BankReceiptController;
use Modules\Finance\Controllers\Web\BankReconciliationController;
use Modules\Finance\Controllers\Web\BudgetController;
use Modules\Finance\Controllers\Web\BudgetLineController;
use Modules\Finance\Controllers\Web\CashAccountController;
use Modules\Finance\Controllers\Web\CostCenterController;
use Modules\Finance\Controllers\Web\CustomerController;
use Modules\Finance\Controllers\Web\CustomerInvoiceController;
use Modules\Finance\Controllers\Web\CustomerStatementController;
use Modules\Finance\Controllers\Web\JournalController;
use Modules\Finance\Controllers\Web\PaymentController;
use Modules\Finance\Controllers\Web\ReceiptController;
use Modules\Finance\Controllers\Web\RecurringJournalController;
use Modules\Finance\Controllers\Web\ReportController;
use Modules\Finance\Controllers\Web\SupplierController;
use Modules\Finance\Controllers\Web\SupplierCreditNoteController;
use Modules\Finance\Controllers\Web\SupplierDebitNoteController;
use Modules\Finance\Controllers\Web\SupplierInvoiceController;
use Modules\Finance\Controllers\Web\SupplierInvoiceLineController;
use Modules\Finance\Controllers\Web\SupplierStatementController;
use Modules\Finance\Controllers\Web\TaxController;
use Modules\Workflow\Controllers\Web\WorkflowController;

Route::get('/', function () {
    return redirect('/login');
});

Route::middleware(['auth', 'verified', 'company.and.branch'])->group(function () {
    Route::get('/companies', [CompanyController::class, 'index'])->middleware('permission:core.companies.view')->name('core.companies.index');
    Route::get('/companies/create', [CompanyController::class, 'create'])->middleware('permission:core.companies.create')->name('core.companies.create');
    Route::post('/companies', [CompanyController::class, 'store'])->middleware('permission:core.companies.create')->name('core.companies.store');
    Route::get('/companies/{id}/edit', [CompanyController::class, 'edit'])->middleware('permission:core.companies.update')->name('core.companies.edit');
    Route::put('/companies/{id}', [CompanyController::class, 'update'])->middleware('permission:core.companies.update')->name('core.companies.update');
    Route::delete('/companies/{id}', [CompanyController::class, 'destroy'])->middleware('permission:core.companies.delete')->name('core.companies.destroy');

    // Users
    Route::get('/users', [UserController::class, 'index'])->middleware('permission:core.users.view')->name('core.users.index');
    Route::get('/users/create', [UserController::class, 'create'])->middleware('permission:core.users.create')->name('core.users.create');
    Route::post('/users', [UserController::class, 'store'])->middleware('permission:core.users.create')->name('core.users.store');
    Route::get('/users/{id}/edit', [UserController::class, 'edit'])->middleware('permission:core.users.update')->name('core.users.edit');
    Route::put('/users/{id}', [UserController::class, 'update'])->middleware('permission:core.users.update')->name('core.users.update');
    Route::delete('/users/{id}', [UserController::class, 'destroy'])->middleware('permission:core.users.delete')->name('core.users.destroy');

    // Roles
    Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:core.roles.view')->name('core.roles.index');
    Route::get('/roles/create', [RoleController::class, 'create'])->middleware('permission:core.roles.create')->name('core.roles.create');
    Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:core.roles.create')->name('core.roles.store');
    Route::get('/roles/{id}/edit', [RoleController::class, 'edit'])->middleware('permission:core.roles.update')->name('core.roles.edit');
    Route::put('/roles/{id}', [RoleController::class, 'update'])->middleware('permission:core.roles.update')->name('core.roles.update');
    Route::delete('/roles/{id}', [RoleController::class, 'destroy'])->middleware('permission:core.roles.delete')->name('core.roles.destroy');

    // Permissions
    Route::get('/permissions', [PermissionController::class, 'index'])->middleware('permission:core.permissions.view')->name('core.permissions.index');
    Route::get('/permissions/create', [PermissionController::class, 'create'])->middleware('permission:core.permissions.create')->name('core.permissions.create');
    Route::post('/permissions', [PermissionController::class, 'store'])->middleware('permission:core.permissions.create')->name('core.permissions.store');
    Route::get('/permissions/{id}/edit', [PermissionController::class, 'edit'])->middleware('permission:core.permissions.update')->name('core.permissions.edit');
    Route::put('/permissions/{id}', [PermissionController::class, 'update'])->middleware('permission:core.permissions.update')->name('core.permissions.update');
    Route::delete('/permissions/{id}', [PermissionController::class, 'destroy'])->middleware('permission:core.permissions.delete')->name('core.permissions.destroy');

    // Audit
    Route::get('/audit', [AuditController::class, 'index'])->middleware('permission:core.audit.view')->name('core.audit.index');
    Route::get('/audit/{id}', [AuditController::class, 'show'])->middleware('permission:core.audit.view')->name('core.audit.show');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('core.notifications.index');
    Route::get('/notifications/{id}', [NotificationController::class, 'show'])->name('core.notifications.show');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('core.notifications.mark-read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('core.notifications.mark-all-read');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('core.notifications.destroy');
    Route::delete('/notifications', [NotificationController::class, 'destroyAll'])->name('core.notifications.destroy-all');

    // Workflow
    Route::get('/workflows', [WorkflowController::class, 'index'])->middleware('permission:core.workflow.view')->name('workflow.index');
    Route::get('/workflows/{id}', [WorkflowController::class, 'show'])->whereNumber('id')->middleware('permission:core.workflow.view')->name('workflow.show');
    Route::get('/workflows/definitions', [WorkflowController::class, 'definitions'])->middleware('permission:core.workflow.manage')->name('workflow.definitions');
    Route::post('/workflows/definitions', [WorkflowController::class, 'storeDefinition'])->middleware('permission:core.workflow.manage')->name('workflow.definitions.store');
    Route::put('/workflows/definitions/{id}', [WorkflowController::class, 'updateDefinition'])->middleware('permission:core.workflow.manage')->name('workflow.definitions.update');

    Route::get('/exchange-rates', [ExchangeRateController::class, 'index'])->middleware('permission:core.exchange-rates.view')->name('core.exchange-rates.index');
    Route::get('/exchange-rates/create', [ExchangeRateController::class, 'create'])->middleware('permission:core.exchange-rates.create')->name('core.exchange-rates.create');
    Route::post('/exchange-rates', [ExchangeRateController::class, 'store'])->middleware('permission:core.exchange-rates.create')->name('core.exchange-rates.store');
    Route::get('/exchange-rates/{id}/edit', [ExchangeRateController::class, 'edit'])->middleware('permission:core.exchange-rates.update')->name('core.exchange-rates.edit');
    Route::put('/exchange-rates/{id}', [ExchangeRateController::class, 'update'])->middleware('permission:core.exchange-rates.update')->name('core.exchange-rates.update');
    Route::delete('/exchange-rates/{id}', [ExchangeRateController::class, 'destroy'])->middleware('permission:core.exchange-rates.delete')->name('core.exchange-rates.destroy');
    Route::get('/periods', [PeriodClosingController::class, 'index'])->middleware('permission:core.periods.view')->name('core.periods.index');
    Route::get('/fiscal-years/create', [FiscalYearController::class, 'create'])->middleware('permission:core.fiscal-years.create')->name('core.fiscal-years.create');
    Route::post('/fiscal-years', [FiscalYearController::class, 'store'])->middleware('permission:core.fiscal-years.create')->name('core.fiscal-years.store');

    Route::post('/periods/{id}/close', [PeriodClosingController::class, 'closePeriod'])->middleware('permission:core.periods.close')->name('core.periods.close');
    Route::post('/periods/{id}/reopen', [PeriodClosingController::class, 'reopenPeriod'])->middleware('permission:core.periods.reopen')->name('core.periods.reopen');
    Route::post('/periods/{id}/lock', [PeriodClosingController::class, 'lockPeriod'])->middleware('permission:core.periods.lock')->name('core.periods.lock');
    Route::post('/periods/{id}/validate', [PeriodClosingController::class, 'validatePeriod'])->middleware('permission:core.periods.close')->name('core.periods.validate');

    Route::get('/branches', [BranchController::class, 'index'])->middleware('permission:core.branches.view')->name('core.branches.index');
    Route::get('/branches/create', [BranchController::class, 'create'])->middleware('permission:core.branches.create')->name('core.branches.create');
    Route::post('/branches', [BranchController::class, 'store'])->middleware('permission:core.branches.create')->name('core.branches.store');
    Route::get('/branches/{id}/edit', [BranchController::class, 'edit'])->middleware('permission:core.branches.update')->name('core.branches.edit');
    Route::put('/branches/{id}', [BranchController::class, 'update'])->middleware('permission:core.branches.update')->name('core.branches.update');
    Route::delete('/branches/{id}', [BranchController::class, 'destroy'])->middleware('permission:core.branches.delete')->name('core.branches.destroy');

    Route::get('/departments', [DepartmentController::class, 'index'])->middleware('permission:core.departments.view')->name('core.departments.index');
    Route::get('/departments/create', [DepartmentController::class, 'create'])->middleware('permission:core.departments.create')->name('core.departments.create');
    Route::post('/departments', [DepartmentController::class, 'store'])->middleware('permission:core.departments.create')->name('core.departments.store');
    Route::get('/departments/{id}/edit', [DepartmentController::class, 'edit'])->middleware('permission:core.departments.update')->name('core.departments.edit');
    Route::put('/departments/{id}', [DepartmentController::class, 'update'])->middleware('permission:core.departments.update')->name('core.departments.update');
    Route::delete('/departments/{id}', [DepartmentController::class, 'destroy'])->middleware('permission:core.departments.delete')->name('core.departments.destroy');

    // Main Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Finance Module
    Route::prefix('finance')->name('finance.')->group(function () {

        // Finance Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('permission:finance.dashboard.view')->name('dashboard');

        // Chart of Accounts
        Route::get('/accounts', [AccountController::class, 'index'])->middleware('permission:finance.accounts.view')->name('accounts.index');
        Route::get('/accounts/create', [AccountController::class, 'create'])->middleware('permission:finance.accounts.create')->name('accounts.create');
        Route::post('/accounts', [AccountController::class, 'store'])->middleware('permission:finance.accounts.create')->name('accounts.store');
        Route::get('/accounts/{id}', [AccountController::class, 'show'])->middleware('permission:finance.accounts.view')->name('accounts.show');
        Route::get('/accounts/{id}/edit', [AccountController::class, 'edit'])->middleware('permission:finance.accounts.update')->name('accounts.edit');
        Route::put('/accounts/{id}', [AccountController::class, 'update'])->middleware('permission:finance.accounts.update')->name('accounts.update');
        Route::delete('/accounts/{id}', [AccountController::class, 'destroy'])->middleware('permission:finance.accounts.delete')->name('accounts.destroy');

        // Journals
        Route::get('/journals', [JournalController::class, 'index'])->middleware('permission:finance.journals.view')->name('journals.index');
        Route::get('/journals/create', [JournalController::class, 'create'])->middleware('permission:finance.journals.create')->name('journals.create');
        Route::post('/journals', [JournalController::class, 'store'])->middleware('permission:finance.journals.create')->name('journals.store');
        Route::get('/journals/{id}', [JournalController::class, 'show'])->middleware('permission:finance.journals.view')->name('journals.show');
        Route::get('/journals/{id}/edit', [JournalController::class, 'edit'])->middleware('permission:finance.journals.update')->name('journals.edit');
        Route::put('/journals/{id}', [JournalController::class, 'update'])->middleware('permission:finance.journals.update')->name('journals.update');
        Route::delete('/journals/{id}', [JournalController::class, 'destroy'])->middleware('permission:finance.journals.delete')->name('journals.destroy');
        Route::post('/journals/{id}/submit', [JournalController::class, 'submit'])->middleware('permission:finance.journals.submit')->name('journals.submit');
        Route::post('/journals/{id}/approve', [JournalController::class, 'approve'])->middleware('permission:finance.journals.approve')->name('journals.approve');
        Route::post('/journals/{id}/reject', [JournalController::class, 'reject'])->middleware('permission:finance.journals.reject')->name('journals.reject');
        Route::post('/journals/{id}/post', [JournalController::class, 'post'])->middleware('permission:finance.journals.post')->name('journals.post');
        Route::post('/journals/{id}/reverse', [JournalController::class, 'reverse'])->middleware('permission:finance.journals.reverse')->name('journals.reverse');
        Route::post('/journals/{id}/cancel', [JournalController::class, 'cancel'])->middleware('permission:finance.journals.cancel')->name('journals.cancel');
        Route::post('/journals/{id}/lines', [JournalController::class, 'addLine'])->middleware('permission:finance.journals.update')->name('journals.lines.store');
        Route::put('/journals/lines/{lineId}', [JournalController::class, 'updateLine'])->middleware('permission:finance.journals.update')->name('journals.lines.update');
        Route::delete('/journals/lines/{lineId}', [JournalController::class, 'removeLine'])->middleware('permission:finance.journals.update')->name('journals.lines.destroy');

        // General Ledger
        Route::get('/general-ledger', [JournalController::class, 'generalLedger'])->middleware('permission:finance.ledger.view')->name('general-ledger');

        // Accounts Payable
        Route::get('/ap-aging', [ReportController::class, 'apAging'])->middleware('permission:finance.reports.view')->name('ap-aging');

        // Accounts Receivable
        Route::get('/ar-aging', [ReportController::class, 'arAging'])->middleware('permission:finance.reports.view')->name('ar-aging');

        // Cash & Bank
        Route::get('/bank-accounts', [BankAccountController::class, 'index'])->middleware('permission:finance.bank-accounts.view')->name('bank-accounts.index');
        Route::get('/bank-receipts', [ReceiptController::class, 'bankReceipts'])->middleware('permission:finance.bank-transactions.view')->name('bank-receipts.index');

        // Suppliers
        Route::get('/suppliers', [SupplierController::class, 'index'])->middleware('permission:finance.suppliers.view')->name('suppliers.index');
        Route::get('/suppliers/create', [SupplierController::class, 'create'])->middleware('permission:finance.suppliers.create')->name('suppliers.create');
        Route::post('/suppliers', [SupplierController::class, 'store'])->middleware('permission:finance.suppliers.create')->name('suppliers.store');
        Route::get('/suppliers/{id}', [SupplierController::class, 'show'])->middleware('permission:finance.suppliers.view')->name('suppliers.show');
        Route::get('/suppliers/{id}/edit', [SupplierController::class, 'edit'])->middleware('permission:finance.suppliers.update')->name('suppliers.edit');
        Route::put('/suppliers/{id}', [SupplierController::class, 'update'])->middleware('permission:finance.suppliers.update')->name('suppliers.update');
        Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy'])->middleware('permission:finance.suppliers.delete')->name('suppliers.destroy');

        // Customers
        Route::get('/customers', [CustomerController::class, 'index'])->middleware('permission:finance.customers.view')->name('customers.index');
        Route::get('/customers/create', [CustomerController::class, 'create'])->middleware('permission:finance.customers.create')->name('customers.create');
        Route::post('/customers', [CustomerController::class, 'store'])->middleware('permission:finance.customers.create')->name('customers.store');
        Route::get('/customers/{id}', [CustomerController::class, 'show'])->middleware('permission:finance.customers.view')->name('customers.show');

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/general-ledger', [ReportController::class, 'generalLedger'])->middleware('permission:finance.ledger.view')->name('general-ledger');
            Route::get('/trial-balance', [ReportController::class, 'trialBalance'])->middleware('permission:finance.reports.view')->name('trial-balance');
            Route::get('/profit-loss', [ReportController::class, 'profitLoss'])->middleware('permission:finance.reports.view')->name('profit-loss');
            Route::get('/balance-sheet', [ReportController::class, 'balanceSheet'])->middleware('permission:finance.reports.view')->name('balance-sheet');
            Route::get('/cash-flow', [ReportController::class, 'cashFlow'])->middleware('permission:finance.reports.view')->name('cash-flow');
            Route::get('/ap', [ReportController::class, 'apAging'])->middleware('permission:finance.reports.view')->name('ap');
            Route::get('/ar', [ReportController::class, 'arAging'])->middleware('permission:finance.reports.view')->name('ar');
            Route::get('/payment-register', [ReportController::class, 'paymentRegister'])->middleware('permission:finance.reports.view')->name('payment-register');
            Route::get('/receipt-register', [ReportController::class, 'receiptRegister'])->middleware('permission:finance.reports.view')->name('receipt-register');
            Route::get('/cash-book', [ReportController::class, 'cashBook'])->middleware('permission:finance.reports.view')->name('cash-book');
            Route::get('/bank-book', [ReportController::class, 'bankBook'])->middleware('permission:finance.reports.view')->name('bank-book');
            Route::get('/management', [ReportController::class, 'management'])->middleware('permission:finance.reports.view')->name('management');
            Route::post('/generate-async', [ReportController::class, 'generateAsync'])->middleware('permission:finance.reports.export')->name('generate-async');
        });

        // Payments
        Route::get('/payments', [PaymentController::class, 'index'])->middleware('permission:finance.payments.view')->name('payments.index');
        Route::get('/payments/create', [PaymentController::class, 'create'])->middleware('permission:finance.payments.create')->name('payments.create');
        Route::post('/payments', [PaymentController::class, 'store'])->middleware('permission:finance.payments.create')->name('payments.store');
        Route::get('/payments/{id}', [PaymentController::class, 'show'])->middleware('permission:finance.payments.view')->name('payments.show');
        Route::post('/payments/{id}/submit', [PaymentController::class, 'submit'])->middleware('permission:finance.payments.submit')->name('payments.submit');
        Route::post('/payments/{id}/approve', [PaymentController::class, 'approve'])->middleware('permission:finance.payments.approve')->name('payments.approve');
        Route::post('/payments/{id}/reject', [PaymentController::class, 'reject'])->middleware('permission:finance.payments.reject')->name('payments.reject');
        Route::post('/payments/{id}/post', [PaymentController::class, 'post'])->middleware('permission:finance.payments.post')->name('payments.post');
        Route::post('/payments/{id}/cancel', [PaymentController::class, 'cancel'])->middleware('permission:finance.payments.cancel')->name('payments.cancel');

        // Receipts
        Route::get('/receipts', [ReceiptController::class, 'index'])->middleware('permission:finance.receipts.view')->name('receipts.index');
        Route::get('/receipts/create', [ReceiptController::class, 'create'])->middleware('permission:finance.receipts.create')->name('receipts.create');
        Route::post('/receipts', [ReceiptController::class, 'store'])->middleware('permission:finance.receipts.create')->name('receipts.store');
        Route::get('/receipts/{id}', [ReceiptController::class, 'show'])->middleware('permission:finance.receipts.view')->name('receipts.show');
        Route::post('/receipts/{id}/submit', [ReceiptController::class, 'submit'])->middleware('permission:finance.receipts.submit')->name('receipts.submit');
        Route::post('/receipts/{id}/approve', [ReceiptController::class, 'approve'])->middleware('permission:finance.receipts.approve')->name('receipts.approve');
        Route::post('/receipts/{id}/reject', [ReceiptController::class, 'reject'])->middleware('permission:finance.receipts.reject')->name('receipts.reject');
        Route::post('/receipts/{id}/cancel', [ReceiptController::class, 'cancel'])->middleware('permission:finance.receipts.cancel')->name('receipts.cancel');

        // Supplier Invoices
        Route::get('/supplier-invoices', [SupplierInvoiceController::class, 'index'])->middleware('permission:finance.supplier-invoices.view')->name('supplier-invoices.index');
        Route::get('/supplier-invoices/create', [SupplierInvoiceController::class, 'create'])->middleware('permission:finance.supplier-invoices.create')->name('supplier-invoices.create');
        Route::post('/supplier-invoices', [SupplierInvoiceController::class, 'store'])->middleware('permission:finance.supplier-invoices.create')->name('supplier-invoices.store');
        Route::get('/supplier-invoices/{id}', [SupplierInvoiceController::class, 'show'])->middleware('permission:finance.supplier-invoices.view')->name('supplier-invoices.show');
        Route::get('/supplier-invoices/{id}/edit', [SupplierInvoiceController::class, 'edit'])->middleware('permission:finance.supplier-invoices.update')->name('supplier-invoices.edit');
        Route::put('/supplier-invoices/{id}', [SupplierInvoiceController::class, 'update'])->middleware('permission:finance.supplier-invoices.update')->name('supplier-invoices.update');
        Route::delete('/supplier-invoices/{id}', [SupplierInvoiceController::class, 'destroy'])->middleware('permission:finance.supplier-invoices.delete')->name('supplier-invoices.destroy');
        Route::post('/supplier-invoices/{id}/submit', [SupplierInvoiceController::class, 'submit'])->middleware('permission:finance.supplier-invoices.submit')->name('supplier-invoices.submit');
        Route::post('/supplier-invoices/{id}/approve', [SupplierInvoiceController::class, 'approve'])->middleware('permission:finance.supplier-invoices.approve')->name('supplier-invoices.approve');
        Route::post('/supplier-invoices/{id}/reject', [SupplierInvoiceController::class, 'reject'])->middleware('permission:finance.supplier-invoices.reject')->name('supplier-invoices.reject');
        Route::post('/supplier-invoices/{id}/post', [SupplierInvoiceController::class, 'post'])->middleware('permission:finance.supplier-invoices.post')->name('supplier-invoices.post');
        Route::post('/supplier-invoices/{id}/cancel', [SupplierInvoiceController::class, 'cancel'])->middleware('permission:finance.supplier-invoices.cancel')->name('supplier-invoices.cancel');
        Route::post('/supplier-invoices/{invoiceId}/lines', [SupplierInvoiceLineController::class, 'store'])->middleware('permission:finance.supplier-invoices.update')->name('supplier-invoices.lines.store');
        Route::put('/supplier-invoices/{invoiceId}/lines/{lineId}', [SupplierInvoiceLineController::class, 'update'])->middleware('permission:finance.supplier-invoices.update')->name('supplier-invoices.lines.update');
        Route::delete('/supplier-invoices/{invoiceId}/lines/{lineId}', [SupplierInvoiceLineController::class, 'destroy'])->middleware('permission:finance.supplier-invoices.update')->name('supplier-invoices.lines.destroy');

        // Supplier Credit Notes
        Route::get('/supplier-credit-notes', [SupplierCreditNoteController::class, 'index'])->middleware('permission:finance.supplier-credit-notes.view')->name('supplier-credit-notes.index');
        Route::get('/supplier-credit-notes/create', [SupplierCreditNoteController::class, 'create'])->middleware('permission:finance.supplier-credit-notes.create')->name('supplier-credit-notes.create');
        Route::post('/supplier-credit-notes', [SupplierCreditNoteController::class, 'store'])->middleware('permission:finance.supplier-credit-notes.create')->name('supplier-credit-notes.store');
        Route::get('/supplier-credit-notes/{id}', [SupplierCreditNoteController::class, 'show'])->middleware('permission:finance.supplier-credit-notes.view')->name('supplier-credit-notes.show');
        Route::get('/supplier-credit-notes/{id}/edit', [SupplierCreditNoteController::class, 'edit'])->middleware('permission:finance.supplier-credit-notes.update')->name('supplier-credit-notes.edit');
        Route::put('/supplier-credit-notes/{id}', [SupplierCreditNoteController::class, 'update'])->middleware('permission:finance.supplier-credit-notes.update')->name('supplier-credit-notes.update');
        Route::delete('/supplier-credit-notes/{id}', [SupplierCreditNoteController::class, 'destroy'])->middleware('permission:finance.supplier-credit-notes.delete')->name('supplier-credit-notes.destroy');
        Route::post('/supplier-credit-notes/{id}/submit', [SupplierCreditNoteController::class, 'submit'])->middleware('permission:finance.supplier-credit-notes.submit')->name('supplier-credit-notes.submit');
        Route::post('/supplier-credit-notes/{id}/approve', [SupplierCreditNoteController::class, 'approve'])->middleware('permission:finance.supplier-credit-notes.approve')->name('supplier-credit-notes.approve');
        Route::post('/supplier-credit-notes/{id}/post', [SupplierCreditNoteController::class, 'post'])->middleware('permission:finance.supplier-credit-notes.post')->name('supplier-credit-notes.post');
        Route::post('/supplier-credit-notes/{id}/cancel', [SupplierCreditNoteController::class, 'cancel'])->middleware('permission:finance.supplier-credit-notes.cancel')->name('supplier-credit-notes.cancel');

        // Supplier Debit Notes
        Route::get('/supplier-debit-notes', [SupplierDebitNoteController::class, 'index'])->middleware('permission:finance.supplier-debit-notes.view')->name('supplier-debit-notes.index');
        Route::get('/supplier-debit-notes/create', [SupplierDebitNoteController::class, 'create'])->middleware('permission:finance.supplier-debit-notes.create')->name('supplier-debit-notes.create');
        Route::post('/supplier-debit-notes', [SupplierDebitNoteController::class, 'store'])->middleware('permission:finance.supplier-debit-notes.create')->name('supplier-debit-notes.store');
        Route::get('/supplier-debit-notes/{id}', [SupplierDebitNoteController::class, 'show'])->middleware('permission:finance.supplier-debit-notes.view')->name('supplier-debit-notes.show');
        Route::get('/supplier-debit-notes/{id}/edit', [SupplierDebitNoteController::class, 'edit'])->middleware('permission:finance.supplier-debit-notes.update')->name('supplier-debit-notes.edit');
        Route::put('/supplier-debit-notes/{id}', [SupplierDebitNoteController::class, 'update'])->middleware('permission:finance.supplier-debit-notes.update')->name('supplier-debit-notes.update');
        Route::delete('/supplier-debit-notes/{id}', [SupplierDebitNoteController::class, 'destroy'])->middleware('permission:finance.supplier-debit-notes.delete')->name('supplier-debit-notes.destroy');
        Route::post('/supplier-debit-notes/{id}/post', [SupplierDebitNoteController::class, 'post'])->middleware('permission:finance.supplier-debit-notes.post')->name('supplier-debit-notes.post');
        Route::post('/supplier-debit-notes/{id}/submit', [SupplierDebitNoteController::class, 'submit'])->middleware('permission:finance.supplier-debit-notes.submit')->name('supplier-debit-notes.submit');
        Route::post('/supplier-debit-notes/{id}/approve', [SupplierDebitNoteController::class, 'approve'])->middleware('permission:finance.supplier-debit-notes.approve')->name('supplier-debit-notes.approve');
        Route::post('/supplier-debit-notes/{id}/cancel', [SupplierDebitNoteController::class, 'cancel'])->middleware('permission:finance.supplier-debit-notes.cancel')->name('supplier-debit-notes.cancel');

        // Supplier Statements
        Route::get('/supplier-statements', [SupplierStatementController::class, 'index'])->middleware('permission:finance.suppliers.view')->name('supplier-statements.index');
        Route::get('/supplier-statements/{id}', [SupplierStatementController::class, 'show'])->middleware('permission:finance.suppliers.view')->name('supplier-statements.show');

        // Customer Invoices
        Route::get('/customer-invoices', [CustomerInvoiceController::class, 'index'])->middleware('permission:finance.customer-invoices.view')->name('customer-invoices.index');
        Route::get('/customer-invoices/create', [CustomerInvoiceController::class, 'create'])->middleware('permission:finance.customer-invoices.create')->name('customer-invoices.create');
        Route::post('/customer-invoices', [CustomerInvoiceController::class, 'store'])->middleware('permission:finance.customer-invoices.create')->name('customer-invoices.store');
        Route::get('/customer-invoices/{id}', [CustomerInvoiceController::class, 'show'])->middleware('permission:finance.customer-invoices.view')->name('customer-invoices.show');
        Route::get('/customer-invoices/{id}/edit', [CustomerInvoiceController::class, 'edit'])->middleware('permission:finance.customer-invoices.update')->name('customer-invoices.edit');
        Route::put('/customer-invoices/{id}', [CustomerInvoiceController::class, 'update'])->middleware('permission:finance.customer-invoices.update')->name('customer-invoices.update');
        Route::delete('/customer-invoices/{id}', [CustomerInvoiceController::class, 'destroy'])->middleware('permission:finance.customer-invoices.delete')->name('customer-invoices.destroy');
        Route::post('/customer-invoices/{id}/submit', [CustomerInvoiceController::class, 'submit'])->middleware('permission:finance.customer-invoices.submit')->name('customer-invoices.submit');
        Route::post('/customer-invoices/{id}/approve', [CustomerInvoiceController::class, 'approve'])->middleware('permission:finance.customer-invoices.approve')->name('customer-invoices.approve');
        Route::post('/customer-invoices/{id}/reject', [CustomerInvoiceController::class, 'reject'])->middleware('permission:finance.customer-invoices.reject')->name('customer-invoices.reject');
        Route::post('/customer-invoices/{id}/post', [CustomerInvoiceController::class, 'post'])->middleware('permission:finance.customer-invoices.post')->name('customer-invoices.post');
        Route::post('/customer-invoices/{id}/cancel', [CustomerInvoiceController::class, 'cancel'])->middleware('permission:finance.customer-invoices.cancel')->name('customer-invoices.cancel');

        // Customer Statements
        Route::get('/customer-statements', [CustomerStatementController::class, 'index'])->middleware('permission:finance.customers.view')->name('customer-statements.index');
        Route::get('/customer-statements/{id}', [CustomerStatementController::class, 'show'])->middleware('permission:finance.customers.view')->name('customer-statements.show');

        // Taxes
        Route::get('/taxes', [TaxController::class, 'index'])->middleware('permission:finance.taxes.view')->name('taxes.index');
        Route::get('/taxes/create', [TaxController::class, 'create'])->middleware('permission:finance.taxes.create')->name('taxes.create');
        Route::post('/taxes', [TaxController::class, 'store'])->middleware('permission:finance.taxes.create')->name('taxes.store');
        Route::get('/taxes/{id}', [TaxController::class, 'show'])->middleware('permission:finance.taxes.view')->name('taxes.show');
        Route::get('/taxes/{id}/edit', [TaxController::class, 'edit'])->middleware('permission:finance.taxes.update')->name('taxes.edit');
        Route::put('/taxes/{id}', [TaxController::class, 'update'])->middleware('permission:finance.taxes.update')->name('taxes.update');
        Route::delete('/taxes/{id}', [TaxController::class, 'destroy'])->middleware('permission:finance.taxes.delete')->name('taxes.destroy');

        // Cash Accounts
        Route::get('/cash-accounts', [CashAccountController::class, 'index'])->middleware('permission:finance.cash-accounts.view')->name('cash-accounts.index');
        Route::get('/cash-accounts/create', [CashAccountController::class, 'create'])->middleware('permission:finance.cash-accounts.create')->name('cash-accounts.create');
        Route::post('/cash-accounts', [CashAccountController::class, 'store'])->middleware('permission:finance.cash-accounts.create')->name('cash-accounts.store');
        Route::get('/cash-accounts/{id}/edit', [CashAccountController::class, 'edit'])->middleware('permission:finance.cash-accounts.update')->name('cash-accounts.edit');
        Route::put('/cash-accounts/{id}', [CashAccountController::class, 'update'])->middleware('permission:finance.cash-accounts.update')->name('cash-accounts.update');
        Route::delete('/cash-accounts/{id}', [CashAccountController::class, 'destroy'])->middleware('permission:finance.cash-accounts.delete')->name('cash-accounts.destroy');

        // Bank Accounts
        Route::get('/bank-accounts', [BankAccountController::class, 'index'])->middleware('permission:finance.bank-accounts.view')->name('bank-accounts.index');
        Route::get('/bank-accounts/create', [BankAccountController::class, 'create'])->middleware('permission:finance.bank-accounts.create')->name('bank-accounts.create');
        Route::post('/bank-accounts', [BankAccountController::class, 'store'])->middleware('permission:finance.bank-accounts.create')->name('bank-accounts.store');
        Route::get('/bank-accounts/{id}/edit', [BankAccountController::class, 'edit'])->middleware('permission:finance.bank-accounts.update')->name('bank-accounts.edit');
        Route::put('/bank-accounts/{id}', [BankAccountController::class, 'update'])->middleware('permission:finance.bank-accounts.update')->name('bank-accounts.update');
        Route::delete('/bank-accounts/{id}', [BankAccountController::class, 'destroy'])->middleware('permission:finance.bank-accounts.delete')->name('bank-accounts.destroy');

        // Bank Payments
        Route::get('/bank-payments', [BankPaymentController::class, 'index'])->middleware('permission:finance.bank-transactions.view')->name('bank-payments.index');
        Route::get('/bank-payments/create', [BankPaymentController::class, 'create'])->middleware('permission:finance.bank-transactions.create')->name('bank-payments.create');
        Route::post('/bank-payments', [BankPaymentController::class, 'store'])->middleware('permission:finance.bank-transactions.create')->name('bank-payments.store');

        // Bank Receipts
        Route::get('/bank-receipts', [BankReceiptController::class, 'index'])->middleware('permission:finance.bank-transactions.view')->name('bank-receipts.index');
        Route::get('/bank-receipts/create', [BankReceiptController::class, 'create'])->middleware('permission:finance.bank-transactions.create')->name('bank-receipts.create');
        Route::post('/bank-receipts', [BankReceiptController::class, 'store'])->middleware('permission:finance.bank-transactions.create')->name('bank-receipts.store');

        // Bank Reconciliation
        Route::get('/bank-reconciliation', [BankReconciliationController::class, 'index'])->middleware('permission:finance.bank-reconciliation.view')->name('bank-reconciliation.index');
        Route::get('/bank-reconciliation/create', [BankReconciliationController::class, 'create'])->middleware('permission:finance.bank-reconciliation.create')->name('bank-reconciliation.create');
        Route::post('/bank-reconciliation', [BankReconciliationController::class, 'store'])->middleware('permission:finance.bank-reconciliation.create')->name('bank-reconciliation.store');

        // Recurring Journals
        Route::get('/recurring-journals', [RecurringJournalController::class, 'index'])->middleware('permission:finance.recurring-journals.view')->name('recurring-journals.index');
        Route::get('/recurring-journals/create', [RecurringJournalController::class, 'create'])->middleware('permission:finance.recurring-journals.create')->name('recurring-journals.create');
        Route::post('/recurring-journals', [RecurringJournalController::class, 'store'])->middleware('permission:finance.recurring-journals.create')->name('recurring-journals.store');
        Route::get('/recurring-journals/{id}/edit', [RecurringJournalController::class, 'edit'])->middleware('permission:finance.recurring-journals.update')->name('recurring-journals.edit');
        Route::put('/recurring-journals/{id}', [RecurringJournalController::class, 'update'])->middleware('permission:finance.recurring-journals.update')->name('recurring-journals.update');
        Route::delete('/recurring-journals/{id}', [RecurringJournalController::class, 'destroy'])->middleware('permission:finance.recurring-journals.delete')->name('recurring-journals.destroy');

        // Cost Centers
        Route::get('/cost-centers', [CostCenterController::class, 'index'])->middleware('permission:finance.costcenters.view')->name('cost-centers.index');
        Route::get('/cost-centers/create', [CostCenterController::class, 'create'])->middleware('permission:finance.costcenters.create')->name('cost-centers.create');
        Route::post('/cost-centers', [CostCenterController::class, 'store'])->middleware('permission:finance.costcenters.create')->name('cost-centers.store');
        Route::get('/cost-centers/{id}/edit', [CostCenterController::class, 'edit'])->middleware('permission:finance.costcenters.update')->name('cost-centers.edit');
        Route::put('/cost-centers/{id}', [CostCenterController::class, 'update'])->middleware('permission:finance.costcenters.update')->name('cost-centers.update');
        Route::delete('/cost-centers/{id}', [CostCenterController::class, 'destroy'])->middleware('permission:finance.costcenters.delete')->name('cost-centers.destroy');

        // Budgets
        Route::get('/budgets', [BudgetController::class, 'index'])->middleware('permission:finance.budgets.view')->name('budgets.index');
        Route::get('/budgets/create', [BudgetController::class, 'create'])->middleware('permission:finance.budgets.create')->name('budgets.create');
        Route::post('/budgets', [BudgetController::class, 'store'])->middleware('permission:finance.budgets.create')->name('budgets.store');
        Route::get('/budgets/{id}', [BudgetController::class, 'show'])->middleware('permission:finance.budgets.view')->name('budgets.show');
        Route::get('/budgets/{id}/edit', [BudgetController::class, 'edit'])->middleware('permission:finance.budgets.update')->name('budgets.edit');
        Route::put('/budgets/{id}', [BudgetController::class, 'update'])->middleware('permission:finance.budgets.update')->name('budgets.update');
        Route::delete('/budgets/{id}', [BudgetController::class, 'destroy'])->middleware('permission:finance.budgets.delete')->name('budgets.destroy');
        Route::post('/budgets/{id}/submit', [BudgetController::class, 'submit'])->middleware('permission:finance.budgets.submit')->name('budgets.submit');
        Route::post('/budgets/{id}/approve', [BudgetController::class, 'approve'])->middleware('permission:finance.budgets.approve')->name('budgets.approve');
        Route::post('/budgets/{id}/reject', [BudgetController::class, 'reject'])->middleware('permission:finance.budgets.reject')->name('budgets.reject');
        Route::post('/budgets/{budgetId}/lines', [BudgetLineController::class, 'store'])->middleware('permission:finance.budgets.update')->name('budgets.lines.store');
        Route::put('/budgets/{budgetId}/lines/{lineId}', [BudgetLineController::class, 'update'])->middleware('permission:finance.budgets.update')->name('budgets.lines.update');
        Route::delete('/budgets/{budgetId}/lines/{lineId}', [BudgetLineController::class, 'destroy'])->middleware('permission:finance.budgets.update')->name('budgets.lines.destroy');
        Route::get('/budgets/lines/accounts', [BudgetLineController::class, 'accounts'])->middleware('permission:finance.budgets.view')->name('budgets.lines.accounts');
        Route::get('/budgets/lines/cost-centers', [BudgetLineController::class, 'costCenters'])->middleware('permission:finance.budgets.view')->name('budgets.lines.cost-centers');

        // Budget vs Actual
        Route::get('/budget-vs-actual', [ReportController::class, 'management'])->middleware('permission:finance.reports.view')->name('budget-vs-actual');
    });

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
