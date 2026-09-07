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
use Modules\Finance\Controllers\Web\CostCenterController;
use Modules\Finance\Controllers\Web\CashAccountController;
use Modules\Finance\Controllers\Web\BankAccountController;

Route::get('/', function () {
    return redirect('/login');
});

Route::middleware(['auth', 'verified'])->group(function () {

    // Main Dashboard (redirects to Finance Dashboard)
    Route::get('/dashboard', function () {
        return redirect()->route('finance.dashboard');
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

        // General Ledger
        Route::get('/general-ledger', [JournalController::class, 'generalLedger'])->name('general-ledger');

        // Accounts Payable
        Route::get('/ap-aging', [ReportController::class, 'apReport'])->name('ap-aging');

        // Accounts Receivable
        Route::get('/ar-aging', [ReportController::class, 'arReport'])->name('ar-aging');

        // Cash & Bank
        Route::get('/bank-accounts', [BankAccountController::class, 'index'])->name('bank-accounts.index');

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

        // Customer Invoices
        Route::get('/customer-invoices', [CustomerInvoiceController::class, 'index'])->name('customer-invoices.index');
        Route::get('/customer-invoices/create', [CustomerInvoiceController::class, 'create'])->name('customer-invoices.create');
        Route::post('/customer-invoices', [CustomerInvoiceController::class, 'store'])->name('customer-invoices.store');
        Route::get('/customer-invoices/{id}', [CustomerInvoiceController::class, 'show'])->name('customer-invoices.show');

        // Taxes
        Route::get('/taxes', [TaxController::class, 'index'])->name('taxes.index');
        Route::get('/taxes/create', [TaxController::class, 'create'])->name('taxes.create');
        Route::post('/taxes', [TaxController::class, 'store'])->name('taxes.store');
        Route::get('/taxes/{id}', [TaxController::class, 'show'])->name('taxes.show');

        // Cash Accounts
        Route::get('/cash-accounts', [CashAccountController::class, 'index'])->name('cash-accounts.index');
        Route::get('/cash-accounts/create', [CashAccountController::class, 'create'])->name('cash-accounts.create');
        Route::post('/cash-accounts', [CashAccountController::class, 'store'])->name('cash-accounts.store');
        Route::get('/cash-accounts/{id}/edit', [CashAccountController::class, 'edit'])->name('cash-accounts.edit');
        Route::put('/cash-accounts/{id}', [CashAccountController::class, 'update'])->name('cash-accounts.update');
        Route::delete('/cash-accounts/{id}', [CashAccountController::class, 'destroy'])->name('cash-accounts.destroy');

        // Cost Centers
        Route::get('/cost-centers', [CostCenterController::class, 'index'])->name('cost-centers.index');
        Route::get('/cost-centers/create', [CostCenterController::class, 'create'])->name('cost-centers.create');
        Route::post('/cost-centers', [CostCenterController::class, 'store'])->name('cost-centers.store');
        Route::get('/cost-centers/{id}/edit', [CostCenterController::class, 'edit'])->name('cost-centers.edit');
        Route::put('/cost-centers/{id}', [CostCenterController::class, 'update'])->name('cost-centers.update');
        Route::delete('/cost-centers/{id}', [CostCenterController::class, 'destroy'])->name('cost-centers.destroy');
    });

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
