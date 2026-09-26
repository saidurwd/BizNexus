<?php

use Illuminate\Support\Facades\Route;
use Modules\Sales\Controllers\Web\DeliveryNoteController;
use Modules\Sales\Controllers\Web\QuotationController;
use Modules\Sales\Controllers\Web\SalesDashboardController;
use Modules\Sales\Controllers\Web\SalesOrderController;
use Modules\Sales\Controllers\Web\SalesOrderInvoiceController;
use Modules\Sales\Controllers\Web\SalesPdfController;

/*
| Sales screens: quotations, sales orders and deliveries. Loaded by routes/web.php inside its authenticated,
| company-bound group.
*/

Route::prefix('sales')->name('sales.')->group(function () {
    Route::get('/dashboard', SalesDashboardController::class)->middleware('permission:sales.orders.view')->name('dashboard');

    // Quotations
    Route::get('/quotations', [QuotationController::class, 'index'])->middleware('permission:sales.quotations.view')->name('quotations.index');
    Route::get('/quotations/create', [QuotationController::class, 'create'])->middleware('permission:sales.quotations.manage')->name('quotations.create');
    Route::post('/quotations', [QuotationController::class, 'store'])->middleware('permission:sales.quotations.manage')->name('quotations.store');
    Route::get('/quotations/{id}', [QuotationController::class, 'show'])->whereNumber('id')->middleware('permission:sales.quotations.view')->name('quotations.show');
    Route::get('/quotations/{id}/pdf', [SalesPdfController::class, 'quotation'])->whereNumber('id')->middleware('permission:sales.quotations.view')->name('quotations.pdf');
    Route::get('/quotations/{id}/edit', [QuotationController::class, 'edit'])->whereNumber('id')->middleware('permission:sales.quotations.manage')->name('quotations.edit');
    Route::put('/quotations/{id}', [QuotationController::class, 'update'])->whereNumber('id')->middleware('permission:sales.quotations.manage')->name('quotations.update');
    Route::delete('/quotations/{id}', [QuotationController::class, 'destroy'])->whereNumber('id')->middleware('permission:sales.quotations.manage')->name('quotations.destroy');
    Route::post('/quotations/{id}/send', [QuotationController::class, 'send'])->whereNumber('id')->middleware('permission:sales.quotations.manage')->name('quotations.send');
    Route::post('/quotations/{id}/accept', [QuotationController::class, 'accept'])->whereNumber('id')->middleware('permission:sales.quotations.manage')->name('quotations.accept');
    Route::post('/quotations/{id}/decline', [QuotationController::class, 'decline'])->whereNumber('id')->middleware('permission:sales.quotations.manage')->name('quotations.decline');
    Route::post('/quotations/{id}/convert', [QuotationController::class, 'convert'])->whereNumber('id')->middleware('permission:sales.orders.create')->name('quotations.convert');

    // Sales orders
    Route::get('/orders', [SalesOrderController::class, 'index'])->middleware('permission:sales.orders.view')->name('orders.index');
    Route::get('/orders/create', [SalesOrderController::class, 'create'])->middleware('permission:sales.orders.create')->name('orders.create');
    Route::post('/orders', [SalesOrderController::class, 'store'])->middleware('permission:sales.orders.create')->name('orders.store');
    Route::get('/orders/{id}', [SalesOrderController::class, 'show'])->whereNumber('id')->middleware('permission:sales.orders.view')->name('orders.show');
    Route::get('/orders/{id}/pdf', [SalesPdfController::class, 'order'])->whereNumber('id')->middleware('permission:sales.orders.view')->name('orders.pdf');
    Route::get('/orders/{id}/edit', [SalesOrderController::class, 'edit'])->whereNumber('id')->middleware('permission:sales.orders.create')->name('orders.edit');
    Route::put('/orders/{id}', [SalesOrderController::class, 'update'])->whereNumber('id')->middleware('permission:sales.orders.create')->name('orders.update');
    Route::delete('/orders/{id}', [SalesOrderController::class, 'destroy'])->whereNumber('id')->middleware('permission:sales.orders.create')->name('orders.destroy');
    Route::post('/orders/{id}/confirm', [SalesOrderController::class, 'confirm'])->whereNumber('id')->middleware('permission:sales.orders.confirm')->name('orders.confirm');
    Route::post('/orders/{id}/cancel', [SalesOrderController::class, 'cancel'])->whereNumber('id')->middleware('permission:sales.orders.cancel')->name('orders.cancel');
    Route::post('/orders/{id}/close', [SalesOrderController::class, 'close'])->whereNumber('id')->middleware('permission:sales.orders.cancel')->name('orders.close');
    Route::get('/orders/{id}/invoice', [SalesOrderInvoiceController::class, 'create'])->whereNumber('id')->middleware('permission:finance.customer-invoices.create')->name('orders.invoice.create');
    Route::post('/orders/{id}/invoice', [SalesOrderInvoiceController::class, 'store'])->whereNumber('id')->middleware('permission:finance.customer-invoices.create')->name('orders.invoice.store');

    // Deliveries
    Route::get('/deliveries', [DeliveryNoteController::class, 'index'])->middleware('permission:sales.deliveries.view')->name('deliveries.index');
    Route::get('/orders/{id}/deliver', [DeliveryNoteController::class, 'create'])->whereNumber('id')->middleware('permission:sales.deliveries.create')->name('deliveries.create');
    Route::post('/orders/{id}/deliver', [DeliveryNoteController::class, 'store'])->whereNumber('id')->middleware('permission:sales.deliveries.create')->name('deliveries.store');
    Route::get('/deliveries/{id}', [DeliveryNoteController::class, 'show'])->whereNumber('id')->middleware('permission:sales.deliveries.view')->name('deliveries.show');
    Route::get('/deliveries/{id}/pdf', [DeliveryNoteController::class, 'pdf'])->whereNumber('id')->middleware('permission:sales.deliveries.view')->name('deliveries.pdf');
});
