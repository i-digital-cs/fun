<?php

use App\Http\Controllers\Web\AccountStatusController;
use App\Http\Controllers\Web\Admin\CityController as AdminCityController;
use App\Http\Controllers\Web\Admin\UserModerationController;
use App\Http\Controllers\Web\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Web\Auth\GoogleAuthController;
use App\Http\Controllers\Web\Auth\RegisteredUserController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\Identity\IdentityDocumentController;
use App\Http\Controllers\Web\LocaleController;
use App\Http\Controllers\Web\Profile\LocationPreferenceController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:auth');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:auth');
    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/account/deactivated', [AccountStatusController::class, 'deactivated'])->name('account.deactivated');

    Route::get('/identity', [IdentityDocumentController::class, 'create'])->name('identity.create');
    Route::post('/identity', [IdentityDocumentController::class, 'store'])
        ->middleware('throttle:uploads')
        ->name('identity.store');

    Route::get('/settings/location', [LocationPreferenceController::class, 'edit'])->name('settings.location.edit');
    Route::put('/settings/location', [LocationPreferenceController::class, 'update'])->name('settings.location.update');

    Route::middleware('platform.admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [UserModerationController::class, 'index'])->name('users.index');
        Route::post('/users/{user}/deactivate', [UserModerationController::class, 'deactivate'])->name('users.deactivate');
        Route::post('/users/{user}/reactivate', [UserModerationController::class, 'reactivate'])->name('users.reactivate');

        Route::get('/cities', [AdminCityController::class, 'index'])->name('cities.index');
        Route::get('/cities/create', [AdminCityController::class, 'create'])->name('cities.create');
        Route::post('/cities', [AdminCityController::class, 'store'])->name('cities.store');
        Route::get('/cities/{city}/edit', [AdminCityController::class, 'edit'])->name('cities.edit');
        Route::put('/cities/{city}', [AdminCityController::class, 'update'])->name('cities.update');
        Route::post('/cities/{city}/deactivate', [AdminCityController::class, 'deactivate'])->name('cities.deactivate');
        Route::post('/cities/{city}/activate', [AdminCityController::class, 'activate'])->name('cities.activate');
    });
});
