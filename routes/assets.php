<?php

use Illuminate\Support\Facades\Route;
use Modules\Assets\Controllers\Web\AssetCategoryController;
use Modules\Assets\Controllers\Web\AssetReportController;
use Modules\Assets\Controllers\Web\CapitalisationController;
use Modules\Assets\Controllers\Web\DepreciationRunController;
use Modules\Assets\Controllers\Web\FixedAssetController;

/*
| Fixed asset screens. Loaded by routes/web.php inside its authenticated, company-bound group.
*/

Route::prefix('assets')->name('assets.')->group(function () {
    Route::get('/register', [FixedAssetController::class, 'index'])->middleware('permission:assets.view')->name('assets.index');
    Route::get('/register/create', [FixedAssetController::class, 'create'])->middleware('permission:assets.manage')->name('assets.create');
    Route::post('/register', [FixedAssetController::class, 'store'])->middleware('permission:assets.manage')->name('assets.store');
    Route::get('/register/{id}', [FixedAssetController::class, 'show'])->whereNumber('id')->middleware('permission:assets.view')->name('assets.show');
    Route::get('/register/{id}/edit', [FixedAssetController::class, 'edit'])->whereNumber('id')->middleware('permission:assets.manage')->name('assets.edit');
    Route::put('/register/{id}', [FixedAssetController::class, 'update'])->whereNumber('id')->middleware('permission:assets.manage')->name('assets.update');
    Route::delete('/register/{id}', [FixedAssetController::class, 'destroy'])->whereNumber('id')->middleware('permission:assets.manage')->name('assets.destroy');
    Route::post('/register/{id}/transfer', [FixedAssetController::class, 'transfer'])->whereNumber('id')->middleware('permission:assets.manage')->name('assets.transfer');
    Route::post('/register/{id}/impair', [FixedAssetController::class, 'impair'])->whereNumber('id')->middleware('permission:assets.dispose')->name('assets.impair');
    Route::post('/register/{id}/dispose', [FixedAssetController::class, 'dispose'])->whereNumber('id')->middleware('permission:assets.dispose')->name('assets.dispose');

    Route::get('/capitalise', [CapitalisationController::class, 'index'])->middleware('permission:assets.manage')->name('capitalise.index');
    Route::post('/capitalise', [CapitalisationController::class, 'store'])->middleware('permission:assets.manage')->name('capitalise.store');

    Route::get('/depreciation', [DepreciationRunController::class, 'index'])->middleware('permission:assets.view')->name('depreciation.index');
    Route::post('/depreciation', [DepreciationRunController::class, 'store'])->middleware('permission:assets.depreciate')->name('depreciation.store');
    Route::get('/depreciation/{id}', [DepreciationRunController::class, 'show'])->whereNumber('id')->middleware('permission:assets.view')->name('depreciation.show');
    Route::post('/depreciation/{id}/reverse', [DepreciationRunController::class, 'reverse'])->whereNumber('id')->middleware('permission:assets.depreciate')->name('depreciation.reverse');

    Route::get('/reports/register', [AssetReportController::class, 'register'])->middleware('permission:assets.view')->name('reports.register');
    Route::get('/reports/movements', [AssetReportController::class, 'movements'])->middleware('permission:assets.view')->name('reports.movements');
    Route::get('/reports/forecast', [AssetReportController::class, 'forecast'])->middleware('permission:assets.view')->name('reports.forecast');

    Route::get('/categories', [AssetCategoryController::class, 'index'])->middleware('permission:assets.view')->name('categories.index');
    Route::post('/categories', [AssetCategoryController::class, 'store'])->middleware('permission:assets.setup')->name('categories.store');
    Route::put('/categories/{id}', [AssetCategoryController::class, 'update'])->whereNumber('id')->middleware('permission:assets.setup')->name('categories.update');
    Route::delete('/categories/{id}', [AssetCategoryController::class, 'destroy'])->whereNumber('id')->middleware('permission:assets.setup')->name('categories.destroy');
});
