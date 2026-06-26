<?php

use Illuminate\Support\Facades\Route;
use Modules\CashRegister\Http\Controllers\CashRegisterController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('cashregisters', CashRegisterController::class)->names('cashregister');
});
