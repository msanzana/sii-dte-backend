<?php


use App\Modules\Auth\Presentation\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('internal/auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/companies', [AuthController::class, 'companies']);
    Route::post('/select-company', [AuthController::class, 'selectCompany']);
});

