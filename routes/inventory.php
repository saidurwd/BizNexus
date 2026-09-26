<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Controllers\Web\GoodsReceiptController;
use Modules\Inventory\Controllers\Web\InventoryDashboardController;
use Modules\Inventory\Controllers\Web\ProductCategoryController;
use Modules\Inventory\Controllers\Web\ProductController;
use Modules\Inventory\Controllers\Web\PurchaseCreditNoteController;
use Modules\Inventory\Controllers\Web\PurchaseInvoiceMatchController;
use Modules\Inventory\Controllers\Web\PurchaseOrderController;
use Modules\Inventory\Controllers\Web\PurchaseOrderPdfController;
use Modules\Inventory\Controllers\Web\ReorderController;
use Modules\Inventory\Controllers\Web\StockAdjustmentController;
use Modules\Inventory\Controllers\Web\StockController;
use Modules\Inventory\Controllers\Web\StockTransferController;
use Modules\Inventory\Controllers\Web\SupplierReturnController;
use Modules\Inventory\Controllers\Web\UnitController;
use Modules\Inventory\Controllers\Web\WarehouseController;

/*
| Inventory and purchasing screens. Loaded by routes/web.php inside its authenticated, company-bound group.
*/

Route::prefix('inventory')->name('inventory.')->group(function () {
    Route::get('/dashboard', InventoryDashboardController::class)->middleware('permission:inventory.stock.view')->name('dashboard');

    // Products
    Route::get('/products', [ProductController::class, 'index'])->middleware('permission:inventory.products.view')->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->middleware('permission:inventory.products.manage')->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->middleware('permission:inventory.products.manage')->name('products.store');
    Route::get('/products/{id}', [ProductController::class, 'show'])->whereNumber('id')->middleware('permission:inventory.products.view')->name('products.show');
    Route::get('/products/{id}/edit', [ProductController::class, 'edit'])->whereNumber('id')->middleware('permission:inventory.products.manage')->name('products.edit');
    Route::put('/products/{id}', [ProductController::class, 'update'])->whereNumber('id')->middleware('permission:inventory.products.manage')->name('products.update');
    Route::delete('/products/{id}', [ProductController::class, 'destroy'])->whereNumber('id')->middleware('permission:inventory.products.manage')->name('products.destroy');

    // Stock on hand, movements and valuation
    Route::get('/stock', [StockController::class, 'index'])->middleware('permission:inventory.stock.view')->name('stock.index');
    Route::get('/stock/movements', [StockController::class, 'movements'])->middleware('permission:inventory.stock.view')->name('stock.movements');
    Route::get('/stock/valuation', [StockController::class, 'valuation'])->middleware('permission:inventory.stock.view')->name('stock.valuation');

    // Stock adjustments and counts
    Route::get('/adjustments', [StockAdjustmentController::class, 'index'])->middleware('permission:inventory.stock.view')->name('adjustments.index');
    Route::get('/adjustments/create', [StockAdjustmentController::class, 'create'])->middleware('permission:inventory.adjustments.create')->name('adjustments.create');
    Route::post('/adjustments', [StockAdjustmentController::class, 'store'])->middleware('permission:inventory.adjustments.create')->name('adjustments.store');
    Route::get('/adjustments/{id}', [StockAdjustmentController::class, 'show'])->whereNumber('id')->middleware('permission:inventory.stock.view')->name('adjustments.show');
    Route::get('/adjustments/{id}/edit', [StockAdjustmentController::class, 'edit'])->whereNumber('id')->middleware('permission:inventory.adjustments.create')->name('adjustments.edit');
    Route::put('/adjustments/{id}', [StockAdjustmentController::class, 'update'])->whereNumber('id')->middleware('permission:inventory.adjustments.create')->name('adjustments.update');
    Route::delete('/adjustments/{id}', [StockAdjustmentController::class, 'destroy'])->whereNumber('id')->middleware('permission:inventory.adjustments.create')->name('adjustments.destroy');
    Route::post('/adjustments/{id}/post', [StockAdjustmentController::class, 'post'])->whereNumber('id')->middleware('permission:inventory.adjustments.post')->name('adjustments.post');

    // Transfers between warehouses
    Route::get('/transfers', [StockTransferController::class, 'index'])->middleware('permission:inventory.stock.view')->name('transfers.index');
    Route::get('/transfers/create', [StockTransferController::class, 'create'])->middleware('permission:inventory.transfers.create')->name('transfers.create');
    Route::post('/transfers', [StockTransferController::class, 'store'])->middleware('permission:inventory.transfers.create')->name('transfers.store');
    Route::get('/transfers/{id}', [StockTransferController::class, 'show'])->whereNumber('id')->middleware('permission:inventory.stock.view')->name('transfers.show');

    // Purchase orders
    Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->middleware('permission:inventory.purchase-orders.view')->name('purchase-orders.index');
    Route::get('/purchase-orders/create', [PurchaseOrderController::class, 'create'])->middleware('permission:inventory.purchase-orders.create')->name('purchase-orders.create');
    Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])->middleware('permission:inventory.purchase-orders.create')->name('purchase-orders.store');
    Route::get('/purchase-orders/{id}', [PurchaseOrderController::class, 'show'])->whereNumber('id')->middleware('permission:inventory.purchase-orders.view')->name('purchase-orders.show');
    Route::get('/purchase-orders/{id}/pdf', PurchaseOrderPdfController::class)->whereNumber('id')->middleware('permission:inventory.purchase-orders.view')->name('purchase-orders.pdf');
    Route::get('/purchase-orders/{id}/edit', [PurchaseOrderController::class, 'edit'])->whereNumber('id')->middleware('permission:inventory.purchase-orders.create')->name('purchase-orders.edit');
    Route::put('/purchase-orders/{id}', [PurchaseOrderController::class, 'update'])->whereNumber('id')->middleware('permission:inventory.purchase-orders.create')->name('purchase-orders.update');
    Route::delete('/purchase-orders/{id}', [PurchaseOrderController::class, 'destroy'])->whereNumber('id')->middleware('permission:inventory.purchase-orders.create')->name('purchase-orders.destroy');
    Route::post('/purchase-orders/{id}/submit', [PurchaseOrderController::class, 'submit'])->whereNumber('id')->middleware('permission:inventory.purchase-orders.submit')->name('purchase-orders.submit');
    Route::post('/purchase-orders/{id}/approve', [PurchaseOrderController::class, 'approve'])->whereNumber('id')->middleware('permission:inventory.purchase-orders.approve')->name('purchase-orders.approve');
    Route::post('/purchase-orders/{id}/reject', [PurchaseOrderController::class, 'reject'])->whereNumber('id')->middleware('permission:inventory.purchase-orders.approve')->name('purchase-orders.reject');
    Route::post('/purchase-orders/{id}/cancel', [PurchaseOrderController::class, 'cancel'])->whereNumber('id')->middleware('permission:inventory.purchase-orders.cancel')->name('purchase-orders.cancel');
    Route::post('/purchase-orders/{id}/close', [PurchaseOrderController::class, 'close'])->whereNumber('id')->middleware('permission:inventory.purchase-orders.cancel')->name('purchase-orders.close');
    Route::get('/purchase-orders/{id}/invoice', [PurchaseInvoiceMatchController::class, 'create'])->whereNumber('id')->middleware('permission:finance.supplier-invoices.create')->name('purchase-orders.invoice.create');
    Route::post('/purchase-orders/{id}/invoice', [PurchaseInvoiceMatchController::class, 'store'])->whereNumber('id')->middleware('permission:finance.supplier-invoices.create')->name('purchase-orders.invoice.store');

    // Supplier returns and the credit notes that settle them
    Route::get('/supplier-returns', [SupplierReturnController::class, 'index'])->middleware('permission:inventory.goods-receipts.view')->name('supplier-returns.index');
    Route::get('/purchase-orders/{id}/return', [SupplierReturnController::class, 'create'])->whereNumber('id')->middleware('permission:inventory.supplier-returns.create')->name('supplier-returns.create');
    Route::post('/purchase-orders/{id}/return', [SupplierReturnController::class, 'store'])->whereNumber('id')->middleware('permission:inventory.supplier-returns.create')->name('supplier-returns.store');
    Route::get('/supplier-returns/{id}', [SupplierReturnController::class, 'show'])->whereNumber('id')->middleware('permission:inventory.goods-receipts.view')->name('supplier-returns.show');
    Route::get('/purchase-orders/{id}/credit-note', [PurchaseCreditNoteController::class, 'create'])->whereNumber('id')->middleware('permission:finance.supplier-credit-notes.create')->name('purchase-orders.credit-note.create');
    Route::post('/purchase-orders/{id}/credit-note', [PurchaseCreditNoteController::class, 'store'])->whereNumber('id')->middleware('permission:finance.supplier-credit-notes.create')->name('purchase-orders.credit-note.store');

    // Reorder suggestions
    Route::get('/reorder', [ReorderController::class, 'index'])->middleware('permission:inventory.purchase-orders.create')->name('reorder.index');
    Route::post('/reorder', [ReorderController::class, 'store'])->middleware('permission:inventory.purchase-orders.create')->name('reorder.store');

    // Goods receipts
    Route::get('/goods-receipts', [GoodsReceiptController::class, 'index'])->middleware('permission:inventory.goods-receipts.view')->name('goods-receipts.index');
    Route::get('/purchase-orders/{id}/receive', [GoodsReceiptController::class, 'create'])->whereNumber('id')->middleware('permission:inventory.goods-receipts.create')->name('goods-receipts.create');
    Route::post('/purchase-orders/{id}/receive', [GoodsReceiptController::class, 'store'])->whereNumber('id')->middleware('permission:inventory.goods-receipts.create')->name('goods-receipts.store');
    Route::get('/goods-receipts/{id}', [GoodsReceiptController::class, 'show'])->whereNumber('id')->middleware('permission:inventory.goods-receipts.view')->name('goods-receipts.show');

    // Setup: units, categories, warehouses
    Route::get('/units', [UnitController::class, 'index'])->middleware('permission:inventory.setup.view')->name('units.index');
    Route::post('/units', [UnitController::class, 'store'])->middleware('permission:inventory.setup.manage')->name('units.store');
    Route::put('/units/{id}', [UnitController::class, 'update'])->whereNumber('id')->middleware('permission:inventory.setup.manage')->name('units.update');
    Route::delete('/units/{id}', [UnitController::class, 'destroy'])->whereNumber('id')->middleware('permission:inventory.setup.manage')->name('units.destroy');

    Route::get('/categories', [ProductCategoryController::class, 'index'])->middleware('permission:inventory.setup.view')->name('categories.index');
    Route::post('/categories', [ProductCategoryController::class, 'store'])->middleware('permission:inventory.setup.manage')->name('categories.store');
    Route::put('/categories/{id}', [ProductCategoryController::class, 'update'])->whereNumber('id')->middleware('permission:inventory.setup.manage')->name('categories.update');
    Route::delete('/categories/{id}', [ProductCategoryController::class, 'destroy'])->whereNumber('id')->middleware('permission:inventory.setup.manage')->name('categories.destroy');

    Route::get('/warehouses', [WarehouseController::class, 'index'])->middleware('permission:inventory.setup.view')->name('warehouses.index');
    Route::post('/warehouses', [WarehouseController::class, 'store'])->middleware('permission:inventory.setup.manage')->name('warehouses.store');
    Route::put('/warehouses/{id}', [WarehouseController::class, 'update'])->whereNumber('id')->middleware('permission:inventory.setup.manage')->name('warehouses.update');
    Route::delete('/warehouses/{id}', [WarehouseController::class, 'destroy'])->whereNumber('id')->middleware('permission:inventory.setup.manage')->name('warehouses.destroy');
});
