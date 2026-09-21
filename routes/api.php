<?php

use App\Http\Controllers\Api\Admin\CategoryController as ApiAdminCategoryController;
use App\Http\Controllers\Api\Admin\CityController as ApiAdminCityController;
use App\Http\Controllers\Api\Admin\UserModerationController as ApiUserModerationController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\CategoryListController;
use App\Http\Controllers\Api\CityListController;
use App\Http\Controllers\Api\Identity\IdentityDocumentController as ApiIdentityDocumentController;
use App\Http\Controllers\Api\Profile\LocationPreferenceController as ApiLocationPreferenceController;
use Illuminate\Support\Facades\Route;

Route::get('/cities', CityListController::class);
Route::get('/categories', CategoryListController::class);

Route::middleware('throttle:auth')->group(function () {
    Route::post('/register', [RegisterController::class, 'store']);
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [MeController::class, 'show']);
    Route::post('/logout', [LoginController::class, 'destroy']);

    Route::post('/identity', [ApiIdentityDocumentController::class, 'store'])
        ->middleware('throttle:uploads');

    Route::put('/settings/location', [ApiLocationPreferenceController::class, 'update']);

    Route::middleware('platform.admin')->prefix('admin')->group(function () {
        Route::post('/users/{user}/deactivate', [ApiUserModerationController::class, 'deactivate']);
        Route::post('/users/{user}/reactivate', [ApiUserModerationController::class, 'reactivate']);

        Route::get('/cities', [ApiAdminCityController::class, 'index']);
        Route::post('/cities', [ApiAdminCityController::class, 'store']);
        Route::put('/cities/{city}', [ApiAdminCityController::class, 'update']);
        Route::post('/cities/{city}/deactivate', [ApiAdminCityController::class, 'deactivate']);
        Route::post('/cities/{city}/activate', [ApiAdminCityController::class, 'activate']);

        Route::get('/categories', [ApiAdminCategoryController::class, 'index']);
        Route::post('/categories', [ApiAdminCategoryController::class, 'store']);
        Route::put('/categories/{category}', [ApiAdminCategoryController::class, 'update']);
        Route::post('/categories/{category}/deactivate', [ApiAdminCategoryController::class, 'deactivate']);
        Route::post('/categories/{category}/activate', [ApiAdminCategoryController::class, 'activate']);
    });
});
