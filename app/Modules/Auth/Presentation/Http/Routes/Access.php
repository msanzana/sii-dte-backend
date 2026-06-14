<?php

use App\Modules\Auth\Presentation\Http\Controllers\AuthCompanyAdminController;
use App\Modules\Auth\Presentation\Http\Controllers\AuthController;
use App\Modules\Auth\Presentation\Http\Controllers\AuthPermissionAdminController;
use App\Modules\Auth\Presentation\Http\Controllers\AuthRoleAdminController;
use App\Modules\Auth\Presentation\Http\Controllers\AuthUserAdminController;
use App\Modules\Auth\Presentation\Http\Controllers\AuthUserCompanyAccessAdminController;
use Illuminate\Support\Facades\Route;

Route::get('/me', [AuthController::class, 'me']);

Route::prefix('admin')->group(function () {
    Route::get('/users', [AuthUserAdminController::class, 'index']);
    Route::get('/users/{userId}', [AuthUserAdminController::class, 'show']);
    Route::post('/users', [AuthUserAdminController::class, 'store']);
    Route::put('/users/{userId}', [AuthUserAdminController::class, 'update']);
    Route::post('/users/{userId}/block', [AuthUserAdminController::class, 'block']);
    Route::post('/users/{userId}/unblock', [AuthUserAdminController::class, 'unblock']);

    Route::get('/roles', [AuthRoleAdminController::class, 'index']);
    Route::get('/roles/{roleId}', [AuthRoleAdminController::class, 'show']);
    Route::post('/roles', [AuthRoleAdminController::class, 'store']);
    Route::put('/roles/{roleId}', [AuthRoleAdminController::class, 'update']);
    Route::post('/roles/{roleId}/permissions', [AuthRoleAdminController::class, 'syncPermissions']);
    Route::post('/roles/{roleId}/block', [AuthRoleAdminController::class, 'block']);
    Route::post('/roles/{roleId}/unblock', [AuthRoleAdminController::class, 'unblock']);

    Route::get('/permissions', [AuthPermissionAdminController::class, 'index']);
    Route::get('/permissions/{permissionId}', [AuthPermissionAdminController::class, 'show']);
    Route::post('/permissions', [AuthPermissionAdminController::class, 'store']);
    Route::put('/permissions/{permissionId}', [AuthPermissionAdminController::class, 'update']);
    Route::post('/permissions/{permissionId}/block', [AuthPermissionAdminController::class, 'block']);
    Route::post('/permissions/{permissionId}/unblock', [AuthPermissionAdminController::class, 'unblock']);

    Route::get('/users/{userId}/company-accesses', [AuthUserCompanyAccessAdminController::class, 'index']);
    Route::post('/company-accesses', [AuthUserCompanyAccessAdminController::class, 'store']);
    Route::put('/company-accesses/{accessId}', [AuthUserCompanyAccessAdminController::class, 'update']);
    Route::post('/company-accesses/{accessId}/block', [AuthUserCompanyAccessAdminController::class, 'block']);
    Route::post('/company-accesses/{accessId}/unblock', [AuthUserCompanyAccessAdminController::class, 'unblock']);
    Route::delete('/company-accesses/{accessId}', [AuthUserCompanyAccessAdminController::class, 'destroy']);

    Route::get('/companies', [AuthCompanyAdminController::class, 'index']);
    Route::get('/companies/{companyId}', [AuthCompanyAdminController::class, 'show']);
    Route::post('/companies/{companyId}/block', [AuthCompanyAdminController::class, 'block']);
    Route::post('/companies/{companyId}/unblock', [AuthCompanyAdminController::class, 'unblock']);
});
