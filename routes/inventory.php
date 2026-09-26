<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Controllers\Web\ProductCategoryController;
use Modules\Inventory\Controllers\Web\ProductController;
use Modules\Inventory\Controllers\Web\UnitController;
use Modules\Inventory\Controllers\Web\WarehouseController;

/*
| Inventory and purchasing screens. Loaded by routes/web.php inside its authenticated, company-bound group.
*/

Route::prefix('inventory')->name('inventory.')->group(function () {
    // Products
    Route::get('/products', [ProductController::class, 'index'])->middleware('permission:inventory.products.view')->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->middleware('permission:inventory.products.manage')->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->middleware('permission:inventory.products.manage')->name('products.store');
    Route::get('/products/{id}', [ProductController::class, 'show'])->whereNumber('id')->middleware('permission:inventory.products.view')->name('products.show');
    Route::get('/products/{id}/edit', [ProductController::class, 'edit'])->whereNumber('id')->middleware('permission:inventory.products.manage')->name('products.edit');
    Route::put('/products/{id}', [ProductController::class, 'update'])->whereNumber('id')->middleware('permission:inventory.products.manage')->name('products.update');
    Route::delete('/products/{id}', [ProductController::class, 'destroy'])->whereNumber('id')->middleware('permission:inventory.products.manage')->name('products.destroy');

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
