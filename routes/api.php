<?php

use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\TokenController;
use Illuminate\Support\Facades\Route;
use Modules\Finance\Controllers\AccountController;
use Modules\Finance\Controllers\CustomerController;
use Modules\Finance\Controllers\CustomerInvoiceController;
use Modules\Finance\Controllers\JournalController;
use Modules\Finance\Controllers\PaymentController;
use Modules\Finance\Controllers\ReceiptController;
use Modules\Finance\Controllers\ReportController;
use Modules\Finance\Controllers\SupplierController;
use Modules\Finance\Controllers\SupplierDebitNoteController;
use Modules\Finance\Controllers\SupplierInvoiceController;

Route::prefix('v1')->group(function () {
    Route::post('/tokens', [TokenController::class, 'store'])->middleware('throttle:6,1');
});

Route::prefix('v1')->middleware(['auth:sanctum', 'api.company'])->group(function () {
    Route::delete('/tokens/current', [TokenController::class, 'destroy']);

    Route::prefix('finance')->group(function () {

        Route::get('/accounts', [AccountController::class, 'index'])->middleware('permission:finance.accounts.view');
        Route::get('/accounts/tree', [AccountController::class, 'tree'])->middleware('permission:finance.accounts.view');
        Route::post('/accounts', [AccountController::class, 'store'])->middleware('permission:finance.accounts.create');
        Route::get('/accounts/{id}', [AccountController::class, 'show'])->middleware('permission:finance.accounts.view')->whereNumber('id');
        Route::put('/accounts/{id}', [AccountController::class, 'update'])->middleware('permission:finance.accounts.update')->whereNumber('id');
        Route::delete('/accounts/{id}', [AccountController::class, 'destroy'])->middleware('permission:finance.accounts.delete')->whereNumber('id');
        Route::get('/accounts/{id}/statement', [AccountController::class, 'statement'])->middleware('permission:finance.ledger.view')->whereNumber('id');

        Route::get('/journals', [JournalController::class, 'index'])->middleware('permission:finance.journals.view');
        Route::post('/journals', [JournalController::class, 'store'])->middleware('permission:finance.journals.create');
        Route::get('/journals/{id}', [JournalController::class, 'show'])->middleware('permission:finance.journals.view')->whereNumber('id');
        Route::put('/journals/{id}', [JournalController::class, 'update'])->middleware('permission:finance.journals.update')->whereNumber('id');
        Route::delete('/journals/{id}', [JournalController::class, 'destroy'])->middleware('permission:finance.journals.delete')->whereNumber('id');
        Route::post('/journals/{id}/lines', [JournalController::class, 'addLine'])->middleware('permission:finance.journals.update')->whereNumber('id');
        Route::delete('/journals/{journalId}/lines/{lineId}', [JournalController::class, 'removeLine'])->middleware('permission:finance.journals.update')->whereNumber(['journalId', 'lineId']);
        Route::post('/journals/{id}/submit', [JournalController::class, 'submit'])->middleware('permission:finance.journals.submit')->whereNumber('id');
        Route::post('/journals/{id}/approve', [JournalController::class, 'approve'])->middleware('permission:finance.journals.approve')->whereNumber('id');
        Route::post('/journals/{id}/reject', [JournalController::class, 'reject'])->middleware('permission:finance.journals.reject')->whereNumber('id');
        Route::post('/journals/{id}/post', [JournalController::class, 'post'])->middleware('permission:finance.journals.post')->whereNumber('id');
        Route::post('/journals/{id}/reverse', [JournalController::class, 'reverse'])->middleware('permission:finance.journals.reverse')->whereNumber('id');
        Route::post('/journals/{id}/cancel', [JournalController::class, 'cancel'])->middleware('permission:finance.journals.cancel')->whereNumber('id');

        Route::get('/suppliers', [SupplierController::class, 'index'])->middleware('permission:finance.suppliers.view');
        Route::post('/suppliers', [SupplierController::class, 'store'])->middleware('permission:finance.suppliers.create');
        Route::get('/suppliers/{id}', [SupplierController::class, 'show'])->middleware('permission:finance.suppliers.view')->whereNumber('id');
        Route::put('/suppliers/{id}', [SupplierController::class, 'update'])->middleware('permission:finance.suppliers.update')->whereNumber('id');
        Route::get('/suppliers/{id}/invoices', [SupplierController::class, 'invoices'])->middleware('permission:finance.suppliers.view')->whereNumber('id');
        Route::get('/suppliers/{id}/outstanding', [SupplierController::class, 'outstandingInvoices'])->middleware('permission:finance.suppliers.view')->whereNumber('id');
        Route::get('/suppliers/{id}/aging', [SupplierController::class, 'aging'])->middleware('permission:finance.suppliers.view')->whereNumber('id');

        Route::get('/supplier-invoices', [SupplierInvoiceController::class, 'index'])->middleware('permission:finance.supplier-invoices.view');
        Route::post('/supplier-invoices', [SupplierInvoiceController::class, 'store'])->middleware('permission:finance.supplier-invoices.create');
        Route::get('/supplier-invoices/{id}', [SupplierInvoiceController::class, 'show'])->middleware('permission:finance.supplier-invoices.view')->whereNumber('id');
        Route::post('/supplier-invoices/{id}/post', [SupplierInvoiceController::class, 'post'])->middleware('permission:finance.supplier-invoices.post')->whereNumber('id');
        Route::post('/supplier-invoices/{id}/submit', [SupplierInvoiceController::class, 'submit'])->middleware('permission:finance.supplier-invoices.submit')->whereNumber('id');
        Route::post('/supplier-invoices/{id}/approve', [SupplierInvoiceController::class, 'approve'])->middleware('permission:finance.supplier-invoices.approve')->whereNumber('id');
        Route::post('/supplier-invoices/{id}/reject', [SupplierInvoiceController::class, 'reject'])->middleware('permission:finance.supplier-invoices.reject')->whereNumber('id');
        Route::post('/supplier-invoices/{id}/cancel', [SupplierInvoiceController::class, 'cancel'])->middleware('permission:finance.supplier-invoices.cancel')->whereNumber('id');

        Route::get('/supplier-debit-notes', [SupplierDebitNoteController::class, 'index'])->middleware('permission:finance.supplier-debit-notes.view');
        Route::post('/supplier-debit-notes', [SupplierDebitNoteController::class, 'store'])->middleware('permission:finance.supplier-debit-notes.create');
        Route::get('/supplier-debit-notes/{id}', [SupplierDebitNoteController::class, 'show'])->middleware('permission:finance.supplier-debit-notes.view')->whereNumber('id');
        Route::post('/supplier-debit-notes/{id}/post', [SupplierDebitNoteController::class, 'post'])->middleware('permission:finance.supplier-debit-notes.post')->whereNumber('id');
        Route::post('/supplier-debit-notes/{id}/submit', [SupplierDebitNoteController::class, 'submit'])->middleware('permission:finance.supplier-debit-notes.submit')->whereNumber('id');
        Route::post('/supplier-debit-notes/{id}/approve', [SupplierDebitNoteController::class, 'approve'])->middleware('permission:finance.supplier-debit-notes.approve')->whereNumber('id');
        Route::post('/supplier-debit-notes/{id}/reject', [SupplierDebitNoteController::class, 'reject'])->middleware('permission:finance.supplier-debit-notes.approve')->whereNumber('id');
        Route::post('/supplier-debit-notes/{id}/cancel', [SupplierDebitNoteController::class, 'cancel'])->middleware('permission:finance.supplier-debit-notes.cancel')->whereNumber('id');

        Route::get('/customers', [CustomerController::class, 'index'])->middleware('permission:finance.customers.view');
        Route::post('/customers', [CustomerController::class, 'store'])->middleware('permission:finance.customers.create');
        Route::get('/customers/{id}', [CustomerController::class, 'show'])->middleware('permission:finance.customers.view')->whereNumber('id');
        Route::put('/customers/{id}', [CustomerController::class, 'update'])->middleware('permission:finance.customers.update')->whereNumber('id');
        Route::get('/customers/{id}/invoices', [CustomerController::class, 'invoices'])->middleware('permission:finance.customers.view')->whereNumber('id');
        Route::get('/customers/{id}/outstanding', [CustomerController::class, 'outstandingInvoices'])->middleware('permission:finance.customers.view')->whereNumber('id');
        Route::get('/customers/{id}/aging', [CustomerController::class, 'aging'])->middleware('permission:finance.customers.view')->whereNumber('id');

        Route::get('/branches', [BranchController::class, 'index'])->name('api.branches.index');

        Route::get('/customer-invoices', [CustomerInvoiceController::class, 'index'])->middleware('permission:finance.customer-invoices.view');
        Route::post('/customer-invoices', [CustomerInvoiceController::class, 'store'])->middleware('permission:finance.customer-invoices.create');
        Route::get('/customer-invoices/{id}', [CustomerInvoiceController::class, 'show'])->middleware('permission:finance.customer-invoices.view')->whereNumber('id');
        Route::post('/customer-invoices/{id}/post', [CustomerInvoiceController::class, 'post'])->middleware('permission:finance.customer-invoices.post')->whereNumber('id');
        Route::post('/customer-invoices/{id}/submit', [CustomerInvoiceController::class, 'submit'])->middleware('permission:finance.customer-invoices.submit')->whereNumber('id');
        Route::post('/customer-invoices/{id}/approve', [CustomerInvoiceController::class, 'approve'])->middleware('permission:finance.customer-invoices.approve')->whereNumber('id');
        Route::post('/customer-invoices/{id}/reject', [CustomerInvoiceController::class, 'reject'])->middleware('permission:finance.customer-invoices.reject')->whereNumber('id');
        Route::post('/customer-invoices/{id}/cancel', [CustomerInvoiceController::class, 'cancel'])->middleware('permission:finance.customer-invoices.cancel')->whereNumber('id');

        Route::get('/payments', [PaymentController::class, 'index'])->middleware('permission:finance.payments.view');
        Route::post('/payments', [PaymentController::class, 'store'])->middleware('permission:finance.payments.create');
        Route::get('/payments/{id}', [PaymentController::class, 'show'])->middleware('permission:finance.payments.view')->whereNumber('id');
        Route::post('/payments/{id}/post', [PaymentController::class, 'post'])->middleware('permission:finance.payments.post')->whereNumber('id');
        Route::post('/payments/{id}/submit', [PaymentController::class, 'submit'])->middleware('permission:finance.payments.submit')->whereNumber('id');
        Route::post('/payments/{id}/approve', [PaymentController::class, 'approve'])->middleware('permission:finance.payments.approve')->whereNumber('id');
        Route::post('/payments/{id}/reject', [PaymentController::class, 'reject'])->middleware('permission:finance.payments.reject')->whereNumber('id');
        Route::post('/payments/{id}/cancel', [PaymentController::class, 'cancel'])->middleware('permission:finance.payments.cancel')->whereNumber('id');
        Route::get('/payments/aging', [PaymentController::class, 'aging'])->middleware('permission:finance.reports.view');

        Route::get('/receipts', [ReceiptController::class, 'index'])->middleware('permission:finance.receipts.view');
        Route::post('/receipts', [ReceiptController::class, 'store'])->middleware('permission:finance.receipts.create');
        Route::get('/receipts/{id}', [ReceiptController::class, 'show'])->middleware('permission:finance.receipts.view')->whereNumber('id');
        Route::post('/receipts/{id}/post', [ReceiptController::class, 'post'])->middleware('permission:finance.receipts.post')->whereNumber('id');
        Route::post('/receipts/{id}/submit', [ReceiptController::class, 'submit'])->middleware('permission:finance.receipts.submit')->whereNumber('id');
        Route::post('/receipts/{id}/approve', [ReceiptController::class, 'approve'])->middleware('permission:finance.receipts.approve')->whereNumber('id');
        Route::post('/receipts/{id}/reject', [ReceiptController::class, 'reject'])->middleware('permission:finance.receipts.reject')->whereNumber('id');
        Route::post('/receipts/{id}/cancel', [ReceiptController::class, 'cancel'])->middleware('permission:finance.receipts.cancel')->whereNumber('id');
        Route::get('/receipts/aging', [ReceiptController::class, 'aging'])->middleware('permission:finance.reports.view');

        Route::prefix('reports')->group(function () {
            Route::get('/trial-balance', [ReportController::class, 'trialBalance'])->middleware('permission:finance.reports.view');
            Route::get('/general-ledger', [ReportController::class, 'generalLedger'])->middleware('permission:finance.ledger.view');
            Route::get('/accounts/{id}/statement', [ReportController::class, 'accountStatement'])->middleware('permission:finance.ledger.view')->whereNumber('id');
            Route::get('/profit-loss', [ReportController::class, 'profitAndLoss'])->middleware('permission:finance.reports.view');
            Route::get('/balance-sheet', [ReportController::class, 'balanceSheet'])->middleware('permission:finance.reports.view');
            Route::get('/dashboard', [ReportController::class, 'dashboard'])->middleware('permission:finance.reports.view');
        });
    });
});
