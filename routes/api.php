<?php

use Illuminate\Support\Facades\Route;

Route::prefix('internal/auth')->group(function () {
    require base_path('app/Modules/Auth/Presentation/Http/Routes/public.php');
});

Route::prefix('internal/auth')
    ->middleware(['jwt.precompany'])
    ->group(function () {
        require base_path('app/Modules/Auth/Presentation/Http/Routes/pre_company.php');
    });

Route::middleware(['jwt.access'])->group(function () {
    Route::prefix('internal/auth')->group(function () {
        require base_path('app/Modules/Auth/Presentation/Http/Routes/access.php');
    });

    Route::prefix('internal/dte')->group(function () {
        require base_path('app/Modules/Dte/Presentation/Http/Routes/api.php');
    });
});
