<?php

use App\Http\Controllers\Api\Admin\CategoryController as ApiAdminCategoryController;
use App\Http\Controllers\Api\Admin\CityController as ApiAdminCityController;
use App\Http\Controllers\Api\Admin\EventModerationController as ApiEventModerationController;
use App\Http\Controllers\Api\Admin\ReportModerationController as ApiReportModerationController;
use App\Http\Controllers\Api\Admin\SiteSettingsController as ApiSiteSettingsController;
use App\Http\Controllers\Api\Admin\UserModerationController as ApiUserModerationController;
use App\Http\Controllers\Api\Auth\EmailVerificationController;
use App\Http\Controllers\Api\Auth\GoogleAuthController as ApiGoogleAuthController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\CategoryListController;
use App\Http\Controllers\Api\CityListController;
use App\Http\Controllers\Api\Events\EventChatController as ApiEventChatController;
use App\Http\Controllers\Api\Chat\ChatInboxController as ApiChatInboxController;
use App\Http\Controllers\Api\Events\EventController as ApiEventController;
use App\Http\Controllers\Api\Events\EventFavoriteController as ApiEventFavoriteController;
use App\Http\Controllers\Api\Events\EventMembershipController as ApiEventMembershipController;
use App\Http\Controllers\Api\Events\EventNowPlayingController as ApiEventNowPlayingController;
use App\Http\Controllers\Api\Events\EventRatingController as ApiEventRatingController;
use App\Http\Controllers\Api\Events\EventTicketController as ApiEventTicketController;
use App\Http\Controllers\Api\HomeParticipationsController;
use App\Http\Controllers\Api\Identity\IdentityDocumentController as ApiIdentityDocumentController;
use App\Http\Controllers\Api\Notifications\DeviceTokenController as ApiDeviceTokenController;
use App\Http\Controllers\Api\Notifications\NotificationInboxController as ApiNotificationInboxController;
use App\Http\Controllers\Api\Profile\LocationPreferenceController as ApiLocationPreferenceController;
use App\Http\Controllers\Api\Profile\ProfileController as ApiProfileController;
use App\Http\Controllers\Api\Profile\PublicProfileController as ApiPublicProfileController;
use App\Http\Controllers\Api\Safety\BlockController as ApiBlockController;
use App\Http\Controllers\Api\Safety\ReportController as ApiReportController;
use App\Http\Controllers\Api\SiteBrandingController;
use App\Http\Controllers\Api\SearchSuggestController;
use App\Http\Controllers\Api\WeatherController;
use App\Http\Controllers\Api\ResolveMapsUrlController;
use App\Http\Controllers\Api\SuggestPlacesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Route::get('/site', SiteBrandingController::class);
Route::get('/cities', CityListController::class);
Route::get('/categories', CategoryListController::class);
Route::get('/search/suggest', SearchSuggestController::class)->middleware('throttle:60,1');
Route::get('/events', [ApiEventController::class, 'index']);
Route::get('/events/now-playing', ApiEventNowPlayingController::class);
Route::get('/events/{event}', [ApiEventController::class, 'show'])->whereNumber('event');
Route::get('/events/{event}/ratings', [ApiEventRatingController::class, 'index'])->whereNumber('event');
Route::get('/users/{publicId}', [ApiPublicProfileController::class, 'show'])
    ->where('publicId', \App\Support\UserPublicId::routePattern())
    ->middleware('throttle:60,1');

Route::middleware('throttle:auth')->group(function () {
    Route::post('/register', [RegisterController::class, 'store']);
    Route::post('/login', [LoginController::class, 'store']);
    Route::post('/auth/google', [ApiGoogleAuthController::class, 'store']);
});

Route::middleware('throttle:auth-codes')->group(function () {
    Route::post('/forgot-password', [PasswordResetController::class, 'request']);
    Route::post('/forgot-password/verify', [PasswordResetController::class, 'verify']);
    Route::post('/reset-password', [PasswordResetController::class, 'reset']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [MeController::class, 'show']);
    Route::get('/weather', WeatherController::class)->middleware('throttle:60,1');
    Route::post('/maps/resolve', ResolveMapsUrlController::class)->middleware('throttle:30,1');
    Route::get('/places/suggest', SuggestPlacesController::class)->middleware('throttle:40,1');
    // Sanctum Bearer auth for mobile / API clients (web uses /broadcasting/auth + session).
    Route::post('/broadcasting/auth', function (Request $request) {
        return Broadcast::auth($request);
    });
    Route::post('/logout', [LoginController::class, 'destroy']);

    Route::post('/email/verify', [EmailVerificationController::class, 'verify'])
        ->middleware('throttle:auth-codes');
    Route::post('/email/verify/resend', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:auth-codes');

    Route::get('/identity', [ApiIdentityDocumentController::class, 'show']);
    Route::get('/identity/documents/{document}', [ApiIdentityDocumentController::class, 'showDocument'])
        ->whereNumber('document');
    Route::post('/identity', [ApiIdentityDocumentController::class, 'store'])
        ->middleware('throttle:uploads');

    Route::put('/settings/profile', [ApiProfileController::class, 'update'])
        ->middleware('throttle:uploads');

    Route::get('/home/participations', HomeParticipationsController::class);

    Route::middleware('account.active')->group(function () {
        Route::put('/settings/location', [ApiLocationPreferenceController::class, 'update']);

        Route::post('/reports', [ApiReportController::class, 'store'])->middleware('throttle:reports');
        Route::get('/blocks', [ApiBlockController::class, 'index']);
        Route::post('/users/{user}/block', [ApiBlockController::class, 'store'])
            ->whereNumber('user')
            ->middleware('throttle:blocks');
        Route::delete('/users/{user}/block', [ApiBlockController::class, 'destroy'])
            ->whereNumber('user')
            ->middleware('throttle:blocks');

        Route::get('/my-events', [ApiEventController::class, 'mine']);
        Route::get('/favorites', [ApiEventFavoriteController::class, 'index']);
        Route::post('/events/{event}/favorite', [ApiEventFavoriteController::class, 'toggle'])
            ->whereNumber('event')
            ->middleware('throttle:joins');
        Route::post('/events', [ApiEventController::class, 'store'])->middleware('throttle:uploads');
        Route::put('/events/{event}', [ApiEventController::class, 'update'])->middleware('throttle:uploads');
        Route::post('/events/{event}/publish', [ApiEventController::class, 'publish']);
        Route::post('/events/{event}/cancel', [ApiEventController::class, 'cancel']);
        Route::post('/events/{event}/complete', [ApiEventController::class, 'complete']);

        Route::post('/events/{event}/join', [ApiEventMembershipController::class, 'join'])
            ->middleware('throttle:joins');
        Route::post('/events/{event}/join/cancel', [ApiEventMembershipController::class, 'cancelJoinRequest'])
            ->middleware('throttle:joins');
        Route::get('/events/{event}/requests', [ApiEventMembershipController::class, 'requests']);
        Route::post('/events/{event}/requests/{joinRequest}/accept', [ApiEventMembershipController::class, 'accept']);
        Route::post('/events/{event}/requests/{joinRequest}/reject', [ApiEventMembershipController::class, 'reject']);
        Route::get('/events/{event}/members', [ApiEventMembershipController::class, 'members']);
        Route::post('/events/{event}/members/{user}/promote', [ApiEventMembershipController::class, 'promote']);
        Route::post('/events/{event}/members/{user}/demote', [ApiEventMembershipController::class, 'demote']);
        Route::post('/events/{event}/transfer', [ApiEventMembershipController::class, 'transfer']);
        Route::post('/events/{event}/leave', [ApiEventMembershipController::class, 'leave']);

        Route::get('/chats', [ApiChatInboxController::class, 'index']);
        Route::post('/events/{event}/messages/read', [ApiChatInboxController::class, 'markRead'])
            ->whereNumber('event');

        Route::get('/notifications', [ApiNotificationInboxController::class, 'index']);
        Route::get('/notifications/unread-count', [ApiNotificationInboxController::class, 'unreadCount']);
        Route::post('/notifications/read-all', [ApiNotificationInboxController::class, 'markAllRead']);
        Route::post('/notifications/{notification}/read', [ApiNotificationInboxController::class, 'markRead']);
        Route::post('/device-tokens', [ApiDeviceTokenController::class, 'store'])
            ->middleware('throttle:60,1');
        Route::delete('/device-tokens', [ApiDeviceTokenController::class, 'destroy'])
            ->middleware('throttle:60,1');

        Route::get('/events/{event}/messages', [ApiEventChatController::class, 'index']);
        Route::post('/events/{event}/messages', [ApiEventChatController::class, 'store'])
            ->middleware('throttle:messages');

        Route::get('/events/{event}/ticket', [ApiEventTicketController::class, 'show'])
            ->whereNumber('event');

        Route::get('/events/{event}/ratings/me', [ApiEventRatingController::class, 'mine']);
        Route::post('/events/{event}/ratings', [ApiEventRatingController::class, 'store'])
            ->middleware('throttle:ratings');
    });

    Route::middleware('platform.admin')->prefix('admin')->group(function () {
        Route::post('/users/{user}/deactivate', [ApiUserModerationController::class, 'deactivate']);
        Route::post('/users/{user}/reactivate', [ApiUserModerationController::class, 'reactivate']);

        Route::get('/reports', [ApiReportModerationController::class, 'index']);
        Route::put('/reports/{report}', [ApiReportModerationController::class, 'update']);

        Route::get('/events', [ApiEventModerationController::class, 'index']);
        Route::post('/events/{event}/cancel', [ApiEventModerationController::class, 'cancel']);
        Route::post('/events/{event}/complete', [ApiEventModerationController::class, 'complete']);

        Route::get('/settings', [ApiSiteSettingsController::class, 'show']);
        Route::put('/settings', [ApiSiteSettingsController::class, 'update']);

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
