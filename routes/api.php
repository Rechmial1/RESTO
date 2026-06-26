<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PrintJobController;
use App\Http\Middleware\DesktopUniqueKeyMiddleware;
use App\Http\Controllers\PosApiController;


// called by Electron every X seconds
Route::middleware(DesktopUniqueKeyMiddleware::class)->group(function () {
    Route::get('/test-connection', [PrintJobController::class, 'testConnection']);

    //Multiple job pull
    Route::get('/print-jobs/pull-multiple', [PrintJobController::class, 'pullMultiple']);

    Route::get('/printer-details', [PrintJobController::class, 'printerDetails']);

    // mark a job done/failed
    Route::patch('/print-jobs/{printJob}', [PrintJobController::class, 'update']);
});
// routes/api.php

Route::prefix('partner/orders')->group(function () {
    Route::get('/{status?}', [PosApiController::class, 'getOrders']);
});
