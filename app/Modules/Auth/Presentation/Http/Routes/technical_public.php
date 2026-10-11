<?php

use App\Modules\Auth\Presentation\Http\Controllers\TechnicalAuthController;
use Illuminate\Support\Facades\Route;

Route::post('/technical/login', [TechnicalAuthController::class, 'login']);
Route::post('/technical/refresh', [TechnicalAuthController::class, 'refresh']);
