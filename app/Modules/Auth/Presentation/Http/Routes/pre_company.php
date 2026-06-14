<?php

use App\Modules\Auth\Presentation\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/companies', [AuthController::class, 'companies']);
Route::post('/select-company', [AuthController::class, 'selectCompany']);
