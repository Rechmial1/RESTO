<?php

use Illuminate\Support\Facades\Route;
use Modules\CashRegister\Http\Controllers\CashRegisterController;
use Modules\CashRegister\Http\Controllers\SettingsController;

Route::middleware(['auth', 'verified'])->group(function () {
    // Main pages
    Route::get('cash-register/dashboard', [CashRegisterController::class, 'dashboard'])
        ->middleware(['can:View Cash Register Reports', 'force.open.register'])
        ->name('cashregister.dashboard');
    Route::get('cash-register/cashier', [CashRegisterController::class, 'cashier'])
        ->name('cashregister.cashier');
    Route::get('cash-register/reports', [CashRegisterController::class, 'reports'])
        ->name('cashregister.reports');
    Route::get('cash-register/approvals', \Modules\CashRegister\Livewire\Approvals\ApprovalsList::class)
        ->middleware('can:Approve Cash Register')
        ->name('cashregister.approvals');

    // Denominations CRUD (basic placeholders)
    Route::get('cash-register/denominations', [CashRegisterController::class, 'denominationsIndex'])
        ->middleware('can:Manage Cash Denominations')
        ->name('cashregister.denominations.index');
    
    // Register Settings
    Route::get('cash-register/settings', [SettingsController::class, 'index'])
        ->middleware('can:Manage Cash Register Settings')
        ->name('cashregister.settings');
    
    // Thermal printing
    Route::post('print-thermal-report', [CashRegisterController::class, 'printThermalReport'])
        ->middleware('can:View Cash Register Reports')
        ->name('cashregister.print-thermal-report');
    
    // Browser popup printing
    Route::get('cash-register/print/x-report/{sessionId}', [CashRegisterController::class, 'printXReport'])
        ->middleware('can:View Cash Register Reports')
        ->name('cashregister.print.x-report');
    Route::get('cash-register/print/z-report/{sessionId}', [CashRegisterController::class, 'printZReport'])
        ->middleware('can:View Cash Register Reports')
        ->name('cashregister.print.z-report');

    // Exports
    Route::get('cash-register/export/discrepancy', [CashRegisterController::class, 'exportDiscrepancy'])
        ->name('cashregister.export.discrepancy');
    Route::get('cash-register/export/cash-ledger', [CashRegisterController::class, 'exportCashLedger'])
        ->name('cashregister.export.cash-ledger');
    Route::get('cash-register/export/cash-in-out', [CashRegisterController::class, 'exportCashInOut'])
        ->name('cashregister.export.cash-in-out');
    Route::get('cash-register/export/session-summary', [CashRegisterController::class, 'exportSessionSummary'])
        ->name('cashregister.export.session-summary');
    
    // Resource fallback (keep for future expansion)
    Route::resource('cashregisters', CashRegisterController::class)->names('cashregister');
});
