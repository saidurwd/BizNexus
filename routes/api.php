<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Controllers\AccountController;
use Modules\Finance\Controllers\JournalController;
use Modules\Finance\Controllers\SupplierController;
use Modules\Finance\Controllers\SupplierInvoiceController;
use Modules\Finance\Controllers\CustomerController;
use Modules\Finance\Controllers\CustomerInvoiceController;
use Modules\Finance\Controllers\PaymentController;
use Modules\Finance\Controllers\ReceiptController;
use Modules\Finance\Controllers\ReportController;

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {

    Route::prefix('finance')->group(function () {

        Route::get('/accounts', [AccountController::class, 'index']);
        Route::get('/accounts/tree', [AccountController::class, 'tree']);
        Route::post('/accounts', [AccountController::class, 'store']);
        Route::get('/accounts/{id}', [AccountController::class, 'show']);
        Route::put('/accounts/{id}', [AccountController::class, 'update']);
        Route::delete('/accounts/{id}', [AccountController::class, 'destroy']);
        Route::get('/accounts/{id}/statement', [AccountController::class, 'statement']);

        Route::get('/journals', [JournalController::class, 'index']);
        Route::post('/journals', [JournalController::class, 'store']);
        Route::get('/journals/{id}', [JournalController::class, 'show']);
        Route::put('/journals/{id}', [JournalController::class, 'update']);
        Route::delete('/journals/{id}', [JournalController::class, 'destroy']);
        Route::post('/journals/{id}/lines', [JournalController::class, 'addLine']);
        Route::delete('/journals/{journalId}/lines/{lineId}', [JournalController::class, 'removeLine']);
        Route::post('/journals/{id}/submit', [JournalController::class, 'submit']);
        Route::post('/journals/{id}/approve', [JournalController::class, 'approve']);
        Route::post('/journals/{id}/reject', [JournalController::class, 'reject']);
        Route::post('/journals/{id}/post', [JournalController::class, 'post']);
        Route::post('/journals/{id}/reverse', [JournalController::class, 'reverse']);
        Route::post('/journals/{id}/cancel', [JournalController::class, 'cancel']);

        Route::get('/suppliers', [SupplierController::class, 'index']);
        Route::post('/suppliers', [SupplierController::class, 'store']);
        Route::get('/suppliers/{id}', [SupplierController::class, 'show']);
        Route::put('/suppliers/{id}', [SupplierController::class, 'update']);
        Route::get('/suppliers/{id}/invoices', [SupplierController::class, 'invoices']);
        Route::get('/suppliers/{id}/outstanding', [SupplierController::class, 'outstandingInvoices']);
        Route::get('/suppliers/{id}/aging', [SupplierController::class, 'aging']);

        Route::get('/supplier-invoices', [SupplierInvoiceController::class, 'index']);
        Route::post('/supplier-invoices', [SupplierInvoiceController::class, 'store']);
        Route::get('/supplier-invoices/{id}', [SupplierInvoiceController::class, 'show']);
        Route::post('/supplier-invoices/{id}/post', [SupplierInvoiceController::class, 'post']);
        Route::post('/supplier-invoices/{id}/cancel', [SupplierInvoiceController::class, 'cancel']);

        Route::get('/customers', [CustomerController::class, 'index']);
        Route::post('/customers', [CustomerController::class, 'store']);
        Route::get('/customers/{id}', [CustomerController::class, 'show']);
        Route::put('/customers/{id}', [CustomerController::class, 'update']);
        Route::get('/customers/{id}/invoices', [CustomerController::class, 'invoices']);
        Route::get('/customers/{id}/outstanding', [CustomerController::class, 'outstandingInvoices']);
        Route::get('/customers/{id}/aging', [CustomerController::class, 'aging']);

        Route::get('/customer-invoices', [CustomerInvoiceController::class, 'index']);
        Route::post('/customer-invoices', [CustomerInvoiceController::class, 'store']);
        Route::get('/customer-invoices/{id}', [CustomerInvoiceController::class, 'show']);
        Route::post('/customer-invoices/{id}/post', [CustomerInvoiceController::class, 'post']);
        Route::post('/customer-invoices/{id}/cancel', [CustomerInvoiceController::class, 'cancel']);

        Route::get('/payments', [PaymentController::class, 'index']);
        Route::post('/payments', [PaymentController::class, 'store']);
        Route::get('/payments/{id}', [PaymentController::class, 'show']);
        Route::post('/payments/{id}/post', [PaymentController::class, 'post']);
        Route::get('/payments/aging', [PaymentController::class, 'aging']);

        Route::get('/receipts', [ReceiptController::class, 'index']);
        Route::post('/receipts', [ReceiptController::class, 'store']);
        Route::get('/receipts/{id}', [ReceiptController::class, 'show']);
        Route::post('/receipts/{id}/post', [ReceiptController::class, 'post']);
        Route::get('/receipts/aging', [ReceiptController::class, 'aging']);

        Route::prefix('reports')->group(function () {
            Route::get('/trial-balance', [ReportController::class, 'trialBalance']);
            Route::get('/general-ledger', [ReportController::class, 'generalLedger']);
            Route::get('/accounts/{id}/statement', [ReportController::class, 'accountStatement']);
            Route::get('/profit-loss', [ReportController::class, 'profitAndLoss']);
            Route::get('/balance-sheet', [ReportController::class, 'balanceSheet']);
            Route::get('/dashboard', [ReportController::class, 'dashboard']);
        });
    });
});
