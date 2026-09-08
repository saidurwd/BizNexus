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
use Modules\Finance\Controllers\Web\CustomerInvoiceController;
use Modules\Finance\Controllers\Web\TaxController;
use Modules\Finance\Controllers\Web\BudgetController;
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

    Route::get('/branches', [BranchController::class, 'index'])->name('core.branches.index');
    Route::get('/branches/create', [BranchController::class, 'create'])->name('core.branches.create');
    Route::post('/branches', [BranchController::class, 'store'])->name('core.branches.store');
    Route::get('/branches/{id}/edit', [BranchController::class, 'edit'])->name('core.branches.edit');
    Route::put('/branches/{id}', [BranchController::class, 'update'])->name('core.branches.update');
    Route::delete('/branches/{id}', [BranchController::class, 'destroy'])->name('core.branches.destroy');



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
        });

        // Payments
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/payments/create', [PaymentController::class, 'create'])->name('payments.create');
        Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
        Route::get('/payments/{id}', [PaymentController::class, 'show'])->name('payments.show');

        // Receipts
        Route::get('/receipts', [ReceiptController::class, 'index'])->name('receipts.index');
        Route::get('/receipts/create', [ReceiptController::class, 'create'])->name('receipts.create');
        Route::post('/receipts', [ReceiptController::class, 'store'])->name('receipts.store');
        Route::get('/receipts/{id}', [ReceiptController::class, 'show'])->name('receipts.show');

        // Supplier Invoices
        Route::get('/supplier-invoices', [SupplierInvoiceController::class, 'index'])->name('supplier-invoices.index');
        Route::get('/supplier-invoices/create', [SupplierInvoiceController::class, 'create'])->name('supplier-invoices.create');
        Route::post('/supplier-invoices', [SupplierInvoiceController::class, 'store'])->name('supplier-invoices.store');
        Route::get('/supplier-invoices/{id}', [SupplierInvoiceController::class, 'show'])->name('supplier-invoices.show');

        // Supplier Statements
        Route::get('/supplier-statements', [SupplierStatementController::class, 'index'])->name('supplier-statements.index');
        Route::get('/supplier-statements/{id}', [SupplierStatementController::class, 'show'])->name('supplier-statements.show');

        // Customer Invoices
        Route::get('/customer-invoices', [CustomerInvoiceController::class, 'index'])->name('customer-invoices.index');
        Route::get('/customer-invoices/create', [CustomerInvoiceController::class, 'create'])->name('customer-invoices.create');
        Route::post('/customer-invoices', [CustomerInvoiceController::class, 'store'])->name('customer-invoices.store');
        Route::get('/customer-invoices/{id}', [CustomerInvoiceController::class, 'show'])->name('customer-invoices.show');

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

        // Budget vs Actual
        Route::get('/budget-vs-actual', [ReportController::class, 'budgetVsActual'])->name('budget-vs-actual');
    });

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
