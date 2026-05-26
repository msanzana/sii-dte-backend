<?php

use App\Modules\Dte\Presentation\Http\Controllers\CafController;
use App\Modules\Dte\Presentation\Http\Controllers\CertificateController;
use App\Modules\Dte\Presentation\Http\Controllers\CompanyController;
use App\Modules\Dte\Presentation\Http\Controllers\DteDocumentController;
use App\Modules\Dte\Presentation\Http\Controllers\DteDocumentPreparationController;
use App\Modules\Dte\Presentation\Http\Controllers\DteServiceController;
use App\Modules\Dte\Presentation\Http\Controllers\DteXmlBuildController;
use App\Modules\Dte\Presentation\Http\Resources\DteTedBuildController;
use Illuminate\Support\Facades\Route;


Route::prefix('internal/dte')->group(function() {
    Route::get('/service',[DteServiceController::class, 'show']);
    Route::post('/service/pause',[DteServiceController::class, 'pause']);
    Route::post('/service/resume', [DteServiceController::class, 'resume']);

    Route::post('/companies',[CompanyController::class,'store']);
    Route::post('/centificates',[CertificateController::class,'store']);
    Route::post('/cafs', [CafController::class, 'store']);

    Route::post('/document',[DteDocumentController::class, 'store']);
    Route::post('/documents/{documentId}/prepare-for-xml', [DteDocumentPreparationController::class, 'prepareForXml']);
    Route::post('/documents/{documentId}/build-xml', [DteXmlBuildController::class, 'build']);
    Route::post('/documents/{documentId}/build-ted', [DteTedBuildController::class, 'build']);
});
