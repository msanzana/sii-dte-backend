<?php

use App\Modules\Dte\Presentation\Http\Controllers\DteDocumentController;
use App\Modules\Dte\Presentation\Http\Controllers\DteServiceController;
use Illuminate\Support\Facades\Route;


Route::prefix('internal/dte')->group(function() {
    Route::get('/service',[DteServiceController::class, 'show']);
    Route::port('/service/pause',[DteServiceController::class, 'pause']);
    Route::post('/service/resume', [DteServiceController::class, 'resume']);

    Route::post('/document',[DteDocumentController::class, 'store']);
});
