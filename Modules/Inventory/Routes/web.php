<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\UnitController;
use Modules\Inventory\Http\Controllers\InventoryItemController;
use Modules\Inventory\Http\Controllers\InventoryItemCategoryController;
use Modules\Inventory\Http\Controllers\InventoryStockController;
use Modules\Inventory\Http\Controllers\InventoryMovementController;
use Modules\Inventory\Http\Controllers\InventoryRecipeController;
use Modules\Inventory\Http\Controllers\InventorySettingController;
use Modules\Inventory\Http\Controllers\PurchaseOrderController;
use Modules\Inventory\Http\Controllers\ReportController;
use Modules\Inventory\Livewire\PurchaseOrder\PurchaseOrderList;
use Modules\Inventory\Http\Controllers\InventoryDashboardController;
use Modules\Inventory\Http\Controllers\SupplierController;
use Modules\Inventory\Http\Controllers\BatchRecipeController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['auth', config('jetstream.auth_session'), 'verified'])->prefix('inventory')->group(function () {
    Route::get('dashboard', [InventoryDashboardController::class, 'index'])->name('inventory.dashboard');
    Route::resource('units', UnitController::class);
    Route::resource('inventory-item-categories', InventoryItemCategoryController::class);
    Route::resource('inventory-items', InventoryItemController::class);
    Route::resource('inventory-stocks', InventoryStockController::class);
    Route::get('inventory-movements/export', [InventoryMovementController::class, 'export'])->name('inventory-movements.export');
    Route::resource('inventory-movements', InventoryMovementController::class);
    Route::resource('recipes', InventoryRecipeController::class);
    Route::get('batch-recipes', [BatchRecipeController::class, 'index'])->name('batch-recipes.index');
    Route::get('batch-inventory', [BatchRecipeController::class, 'inventory'])->name('batch-inventory.index');
    Route::resource('purchase-orders', PurchaseOrderController::class);
    Route::resource('suppliers', SupplierController::class);
    Route::resource('inventory-settings', InventorySettingController::class);
    Route::controller(PurchaseOrderController::class)->group(function () {
        Route::get('purchase-orders/{purchase_order}/pdf', 'generatePdf')->name('purchase-orders.pdf');
    });

    // Inventory Reports Section
    Route::prefix('reports')->name('inventory.reports.')->group(function () {
        Route::get('usage', [ReportController::class, 'usage'])->name('usage');
        Route::get('turnover', [ReportController::class, 'turnover'])->name('turnover');
        Route::get('forecasting', [ReportController::class, 'forecasting'])->name('forecasting');
        Route::get('cogs', [ReportController::class, 'cogs'])->name('cogs');
        Route::get('profit-and-loss', [ReportController::class, 'profitAndLoss'])->name('profit-and-loss');
        Route::get('purchase-orders', [ReportController::class, 'purchaseOrders'])->name('purchase-orders');
    });

    // Batch Reports Section
    Route::prefix('batch-reports')->name('inventory.batch-reports.')->group(function () {
        Route::get('production', [ReportController::class, 'batchProduction'])->name('production');
        Route::get('consumption', [ReportController::class, 'batchConsumption'])->name('consumption');
        Route::get('expected-vs-actual', [ReportController::class, 'batchExpectedVsActual'])->name('expected-actual');
        Route::get('waste-expiry', [ReportController::class, 'batchWasteExpiry'])->name('waste-expiry');
        Route::get('cogs', [ReportController::class, 'batchCogs'])->name('cogs');
    });
});
