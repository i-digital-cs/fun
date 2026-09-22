<?php

use App\Http\Controllers\Web\AccountStatusController;
use App\Http\Controllers\Web\Admin\AuditLogController;
use App\Http\Controllers\Web\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Web\Admin\ChatModerationController;
use App\Http\Controllers\Web\Admin\CityController as AdminCityController;
use App\Http\Controllers\Web\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Web\Admin\EventModerationController;
use App\Http\Controllers\Web\Admin\IdentityDocumentModerationController;
use App\Http\Controllers\Web\Admin\ReportModerationController;
use App\Http\Controllers\Web\Admin\SiteSettingsController;
use App\Http\Controllers\Web\Admin\UserModerationController;
use App\Http\Controllers\Web\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Web\Auth\GoogleAuthController;
use App\Http\Controllers\Web\Auth\RegisteredUserController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\Events\EventChatController;
use App\Http\Controllers\Web\Events\EventController;
use App\Http\Controllers\Web\Events\EventMembershipController;
use App\Http\Controllers\Web\Events\EventRatingController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\Identity\IdentityDocumentController;
use App\Http\Controllers\Web\LocaleController;
use App\Http\Controllers\Web\Profile\LocationPreferenceController;
use App\Http\Controllers\Web\Safety\BlockController;
use App\Http\Controllers\Web\Safety\ReportController;
use App\Http\Controllers\Web\Safety\SafetyGuidelinesController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

Route::get('/safety', SafetyGuidelinesController::class)->name('safety.guidelines');

Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/{event}', [EventController::class, 'show'])
    ->whereNumber('event')
    ->name('events.show');

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
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/account/deactivated', [AccountStatusController::class, 'deactivated'])->name('account.deactivated');

    Route::get('/identity', [IdentityDocumentController::class, 'create'])->name('identity.create');
    Route::post('/identity', [IdentityDocumentController::class, 'store'])
        ->middleware('throttle:uploads')
        ->name('identity.store');

    Route::middleware('account.active')->group(function () {
        Route::get('/settings/location', [LocationPreferenceController::class, 'edit'])->name('settings.location.edit');
        Route::put('/settings/location', [LocationPreferenceController::class, 'update'])->name('settings.location.update');

        Route::get('/report', [ReportController::class, 'create'])->name('reports.create');
        Route::post('/report', [ReportController::class, 'store'])
            ->middleware('throttle:reports')
            ->name('reports.store');
        Route::get('/settings/blocks', [BlockController::class, 'index'])->name('blocks.index');
        Route::post('/users/{user}/block', [BlockController::class, 'store'])
            ->whereNumber('user')
            ->middleware('throttle:blocks')
            ->name('users.block');
        Route::delete('/users/{user}/block', [BlockController::class, 'destroy'])
            ->whereNumber('user')
            ->middleware('throttle:blocks')
            ->name('users.unblock');

        Route::get('/my-events', [EventController::class, 'mine'])->name('events.mine');
        Route::get('/events/create', [EventController::class, 'create'])->name('events.create');
        Route::post('/events', [EventController::class, 'store'])
            ->middleware('throttle:uploads')
            ->name('events.store');
        Route::get('/events/{event}/edit', [EventController::class, 'edit'])
            ->whereNumber('event')
            ->name('events.edit');
        Route::put('/events/{event}', [EventController::class, 'update'])
            ->whereNumber('event')
            ->middleware('throttle:uploads')
            ->name('events.update');
        Route::post('/events/{event}/publish', [EventController::class, 'publish'])
            ->whereNumber('event')
            ->name('events.publish');
        Route::post('/events/{event}/cancel', [EventController::class, 'cancel'])
            ->whereNumber('event')
            ->name('events.cancel');
        Route::post('/events/{event}/complete', [EventController::class, 'complete'])
            ->whereNumber('event')
            ->name('events.complete');

        Route::post('/events/{event}/join', [EventMembershipController::class, 'join'])
            ->whereNumber('event')
            ->middleware('throttle:joins')
            ->name('events.join');
        Route::get('/events/{event}/requests', [EventMembershipController::class, 'requests'])
            ->whereNumber('event')
            ->name('events.requests');
        Route::post('/events/{event}/requests/{joinRequest}/accept', [EventMembershipController::class, 'accept'])
            ->whereNumber('event')
            ->name('events.requests.accept');
        Route::post('/events/{event}/requests/{joinRequest}/reject', [EventMembershipController::class, 'reject'])
            ->whereNumber('event')
            ->name('events.requests.reject');
        Route::get('/events/{event}/members', [EventMembershipController::class, 'members'])
            ->whereNumber('event')
            ->name('events.members');
        Route::post('/events/{event}/members/{user}/promote', [EventMembershipController::class, 'promote'])
            ->whereNumber('event')
            ->name('events.members.promote');
        Route::post('/events/{event}/members/{user}/demote', [EventMembershipController::class, 'demote'])
            ->whereNumber('event')
            ->name('events.members.demote');
        Route::post('/events/{event}/transfer', [EventMembershipController::class, 'transfer'])
            ->whereNumber('event')
            ->name('events.transfer');
        Route::post('/events/{event}/leave', [EventMembershipController::class, 'leave'])
            ->whereNumber('event')
            ->name('events.leave');

        Route::get('/events/{event}/chat', [EventChatController::class, 'show'])
            ->whereNumber('event')
            ->name('events.chat');
        Route::post('/events/{event}/chat', [EventChatController::class, 'store'])
            ->whereNumber('event')
            ->middleware('throttle:messages')
            ->name('events.chat.store');

        Route::post('/events/{event}/ratings', [EventRatingController::class, 'store'])
            ->whereNumber('event')
            ->middleware('throttle:ratings')
            ->name('events.ratings.store');
    });

    Route::middleware('platform.admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        Route::get('/users', [UserModerationController::class, 'index'])->name('users.index');
        Route::post('/users/{user}/deactivate', [UserModerationController::class, 'deactivate'])->name('users.deactivate');
        Route::post('/users/{user}/reactivate', [UserModerationController::class, 'reactivate'])->name('users.reactivate');

        Route::get('/identity', [IdentityDocumentModerationController::class, 'index'])->name('identity.index');
        Route::get('/identity/users/{user}', [IdentityDocumentModerationController::class, 'show'])->name('identity.show');
        Route::get('/identity/documents/{document}', [IdentityDocumentModerationController::class, 'download'])
            ->whereNumber('document')
            ->name('identity.download');

        Route::get('/events', [EventModerationController::class, 'index'])->name('events.index');
        Route::post('/events/{event}/cancel', [EventModerationController::class, 'cancel'])
            ->whereNumber('event')
            ->name('events.cancel');
        Route::post('/events/{event}/complete', [EventModerationController::class, 'complete'])
            ->whereNumber('event')
            ->name('events.complete');

        Route::get('/events/{event}/chat', [ChatModerationController::class, 'index'])
            ->whereNumber('event')
            ->name('chat.index');
        Route::delete('/events/{event}/chat/{message}', [ChatModerationController::class, 'destroy'])
            ->whereNumber('event')
            ->name('chat.destroy');

        Route::get('/reports', [ReportModerationController::class, 'index'])->name('reports.index');
        Route::put('/reports/{report}', [ReportModerationController::class, 'update'])->name('reports.update');

        Route::get('/settings', [SiteSettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SiteSettingsController::class, 'update'])->name('settings.update');

        Route::get('/audit', [AuditLogController::class, 'index'])->name('audit.index');

        Route::get('/cities', [AdminCityController::class, 'index'])->name('cities.index');
        Route::get('/cities/create', [AdminCityController::class, 'create'])->name('cities.create');
        Route::post('/cities', [AdminCityController::class, 'store'])->name('cities.store');
        Route::get('/cities/{city}/edit', [AdminCityController::class, 'edit'])->name('cities.edit');
        Route::put('/cities/{city}', [AdminCityController::class, 'update'])->name('cities.update');
        Route::post('/cities/{city}/deactivate', [AdminCityController::class, 'deactivate'])->name('cities.deactivate');
        Route::post('/cities/{city}/activate', [AdminCityController::class, 'activate'])->name('cities.activate');

        Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
        Route::get('/categories/create', [AdminCategoryController::class, 'create'])->name('categories.create');
        Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
        Route::get('/categories/{category}/edit', [AdminCategoryController::class, 'edit'])->name('categories.edit');
        Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
        Route::post('/categories/{category}/deactivate', [AdminCategoryController::class, 'deactivate'])->name('categories.deactivate');
        Route::post('/categories/{category}/activate', [AdminCategoryController::class, 'activate'])->name('categories.activate');
    });
});
