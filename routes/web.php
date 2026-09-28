<?php

use App\Http\Controllers\Web\AccountStatusController;
use App\Http\Controllers\Web\Account\DeleteAccountController;
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
use App\Http\Controllers\Web\Auth\EmailVerificationController;
use App\Http\Controllers\Web\Auth\GoogleAuthController;
use App\Http\Controllers\Web\Auth\PasswordResetController;
use App\Http\Controllers\Web\Auth\RegisteredUserController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\Events\EventChatController;
use App\Http\Controllers\Web\Chat\ChatInboxController;
use App\Http\Controllers\Web\Friends\DirectMessageController as WebDirectMessageController;
use App\Http\Controllers\Web\Friends\FriendController as WebFriendController;
use App\Http\Controllers\Web\Events\EventController;
use App\Http\Controllers\Web\Events\EventFavoriteController;
use App\Http\Controllers\Web\Events\EventMembershipController;
use App\Http\Controllers\Web\Events\EventRatingController;
use App\Http\Controllers\Web\Events\EventTicketController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\Identity\IdentityDocumentController;
use App\Http\Controllers\Web\Legal\AccountDeletionHelpController;
use App\Http\Controllers\Web\Legal\PrivacyPolicyController;
use App\Http\Controllers\Web\Legal\SupportController;
use App\Http\Controllers\Web\Legal\TermsOfServiceController;
use App\Http\Controllers\Web\LocaleController;
use App\Http\Controllers\Web\Notifications\NotificationInboxController;
use App\Http\Controllers\Web\RobotsController;
use App\Http\Controllers\Web\SitemapController;
use App\Http\Controllers\Web\Profile\LocationPreferenceController;
use App\Http\Controllers\Web\Profile\NotificationSettingsController;
use App\Http\Controllers\Web\Profile\ProfileController;
use App\Http\Controllers\Web\Profile\PublicProfileController;
use App\Http\Controllers\Web\Safety\BlockController;
use App\Http\Controllers\Web\Safety\ReportController;
use App\Http\Controllers\Web\Safety\SafetyGuidelinesController;
use App\Http\Controllers\Web\SearchSuggestController;
use App\Models\Event;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

Route::get('/safety', SafetyGuidelinesController::class)->name('safety.guidelines');
Route::get('/privacy-policy', PrivacyPolicyController::class)->name('legal.privacy');
Route::get('/terms', TermsOfServiceController::class)->name('legal.terms');
Route::get('/support', SupportController::class)->name('legal.support');
Route::get('/account-deletion', AccountDeletionHelpController::class)->name('legal.account-deletion');

Route::get('/search/suggest', SearchSuggestController::class)
    ->middleware('throttle:60,1')
    ->name('search.suggest');

Route::get('/events', [EventController::class, 'index'])->name('events.index');
// events.show is registered AFTER /events/{event}/edit|chat|ticket|… so those paths
// are not captured as optional {slug} (which previously 301'd back to the event page).

Route::get('/u/{publicId}', [PublicProfileController::class, 'show'])
    ->where('publicId', \App\Support\UserPublicId::routePattern())
    ->name('users.public.show');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:auth');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:auth');
    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])
        ->middleware('throttle:auth-codes')
        ->name('password.email');
    Route::get('/forgot-password/verify', [PasswordResetController::class, 'showVerify'])->name('password.verify');
    Route::post('/forgot-password/verify', [PasswordResetController::class, 'verify'])
        ->middleware('throttle:auth-codes')
        ->name('password.verify.store');
    Route::post('/forgot-password/resend', [PasswordResetController::class, 'resend'])
        ->middleware('throttle:auth-codes')
        ->name('password.resend');
    Route::get('/reset-password', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:auth')
        ->name('password.update');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::post('/email/verify', [EmailVerificationController::class, 'verify'])
        ->middleware('throttle:auth-codes')
        ->name('verification.verify');
    Route::post('/email/verify/resend', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:auth-codes')
        ->name('verification.send');

    Route::middleware('verified.email')->group(function () {
        Route::get('/dashboard', DashboardController::class)->name('dashboard');

        Route::get('/account/deactivated', [AccountStatusController::class, 'deactivated'])->name('account.deactivated');

        Route::get('/identity', [IdentityDocumentController::class, 'create'])->name('identity.create');
        Route::post('/identity', [IdentityDocumentController::class, 'store'])
            ->middleware('throttle:uploads')
            ->name('identity.store');
        Route::get('/identity/documents/{document}', [IdentityDocumentController::class, 'showDocument'])
            ->whereNumber('document')
            ->name('identity.documents.show');

        Route::get('/settings/delete-account', [DeleteAccountController::class, 'edit'])->name('settings.account.delete');
        Route::delete('/settings/delete-account', [DeleteAccountController::class, 'destroy'])
            ->middleware('throttle:6,1')
            ->name('settings.account.delete.destroy');
        Route::post('/settings/delete-account/cancel', [DeleteAccountController::class, 'cancel'])
            ->middleware('throttle:6,1')
            ->name('settings.account.delete.cancel');

        Route::middleware('account.active')->group(function () {
        Route::get('/settings/profile', [ProfileController::class, 'edit'])->name('settings.profile.edit');
        Route::put('/settings/profile', [ProfileController::class, 'update'])
            ->middleware('throttle:uploads')
            ->name('settings.profile.update');
        Route::get('/settings/location', [LocationPreferenceController::class, 'edit'])->name('settings.location.edit');
        Route::put('/settings/location', [LocationPreferenceController::class, 'update'])->name('settings.location.update');
        Route::get('/settings/notifications', [NotificationSettingsController::class, 'edit'])->name('settings.notifications.edit');
        Route::put('/settings/notifications', [NotificationSettingsController::class, 'update'])->name('settings.notifications.update');

        Route::get('/friends', [WebFriendController::class, 'index'])->name('friends.index');
        Route::post('/friends', [WebFriendController::class, 'store'])->middleware('throttle:30,1')->name('friends.store');
        Route::post('/friends/{user}/accept', [WebFriendController::class, 'accept'])
            ->whereNumber('user')
            ->middleware('throttle:30,1')
            ->name('friends.accept');
        Route::post('/friends/{user}/decline', [WebFriendController::class, 'decline'])
            ->whereNumber('user')
            ->middleware('throttle:30,1')
            ->name('friends.decline');
        Route::post('/friends/{user}/cancel', [WebFriendController::class, 'cancel'])
            ->whereNumber('user')
            ->middleware('throttle:30,1')
            ->name('friends.cancel');
        Route::delete('/friends/{user}', [WebFriendController::class, 'destroy'])
            ->whereNumber('user')
            ->middleware('throttle:30,1')
            ->name('friends.destroy');

        Route::get('/dms/with/{user}', [WebDirectMessageController::class, 'open'])
            ->whereNumber('user')
            ->middleware('throttle:30,1')
            ->name('dms.open');
        Route::get('/dms/{conversation}', [WebDirectMessageController::class, 'show'])->whereNumber('conversation')->name('dms.show');
        Route::post('/dms/{conversation}', [WebDirectMessageController::class, 'store'])
            ->whereNumber('conversation')
            ->middleware('throttle:messages')
            ->name('dms.store');

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
        Route::get('/favorites', [EventFavoriteController::class, 'index'])->name('events.favorites');
        Route::get('/events/create', [EventController::class, 'create'])->name('events.create');
        Route::post('/events', [EventController::class, 'store'])
            ->middleware('throttle:uploads')
            ->name('events.store');

        // Member event actions — always /events/{id}/{slug}/…
        Route::middleware('event.slug')->group(function () {
            $slug = '^[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*$';

            Route::post('/events/{event}/{slug}/favorite', [EventFavoriteController::class, 'toggle'])
                ->whereNumber('event')->where('slug', $slug)
                ->middleware('throttle:joins')
                ->name('events.favorite.toggle');
            Route::get('/events/{event}/{slug}/edit', [EventController::class, 'edit'])
                ->whereNumber('event')->where('slug', $slug)
                ->name('events.edit');
            Route::put('/events/{event}/{slug}', [EventController::class, 'update'])
                ->whereNumber('event')->where('slug', $slug)
                ->middleware('throttle:uploads')
                ->name('events.update');
            Route::post('/events/{event}/{slug}/publish', [EventController::class, 'publish'])
                ->whereNumber('event')->where('slug', $slug)
                ->name('events.publish');
            Route::post('/events/{event}/{slug}/cancel', [EventController::class, 'cancel'])
                ->whereNumber('event')->where('slug', $slug)
                ->name('events.cancel');
            Route::post('/events/{event}/{slug}/complete', [EventController::class, 'complete'])
                ->whereNumber('event')->where('slug', $slug)
                ->name('events.complete');

            Route::post('/events/{event}/{slug}/join', [EventMembershipController::class, 'join'])
                ->whereNumber('event')->where('slug', $slug)
                ->middleware('throttle:joins')
                ->name('events.join');
            Route::post('/events/{event}/{slug}/join/cancel', [EventMembershipController::class, 'cancelJoinRequest'])
                ->whereNumber('event')->where('slug', $slug)
                ->middleware('throttle:joins')
                ->name('events.join.cancel');
            Route::get('/events/{event}/{slug}/requests', [EventMembershipController::class, 'requests'])
                ->whereNumber('event')->where('slug', $slug)
                ->name('events.requests');
            Route::post('/events/{event}/{slug}/requests/{joinRequest}/accept', [EventMembershipController::class, 'accept'])
                ->whereNumber('event')->where('slug', $slug)
                ->name('events.requests.accept');
            Route::post('/events/{event}/{slug}/requests/{joinRequest}/reject', [EventMembershipController::class, 'reject'])
                ->whereNumber('event')->where('slug', $slug)
                ->name('events.requests.reject');
            Route::get('/events/{event}/{slug}/members', [EventMembershipController::class, 'members'])
                ->whereNumber('event')->where('slug', $slug)
                ->name('events.members');
            Route::post('/events/{event}/{slug}/members/{user}/promote', [EventMembershipController::class, 'promote'])
                ->whereNumber('event')->where('slug', $slug)
                ->name('events.members.promote');
            Route::post('/events/{event}/{slug}/members/{user}/demote', [EventMembershipController::class, 'demote'])
                ->whereNumber('event')->where('slug', $slug)
                ->name('events.members.demote');
            Route::post('/events/{event}/{slug}/transfer', [EventMembershipController::class, 'transfer'])
                ->whereNumber('event')->where('slug', $slug)
                ->name('events.transfer');
            Route::post('/events/{event}/{slug}/leave', [EventMembershipController::class, 'leave'])
                ->whereNumber('event')->where('slug', $slug)
                ->name('events.leave');

            Route::get('/events/{event}/{slug}/chat', [EventChatController::class, 'show'])
                ->whereNumber('event')->where('slug', $slug)
                ->name('events.chat');
            Route::post('/events/{event}/{slug}/chat', [EventChatController::class, 'store'])
                ->whereNumber('event')->where('slug', $slug)
                ->middleware('throttle:messages')
                ->name('events.chat.store');
            Route::post('/events/{event}/{slug}/chat/{message}/react', [EventChatController::class, 'react'])
                ->whereNumber('event')->where('slug', $slug)->whereNumber('message')
                ->middleware('throttle:60,1')
                ->name('events.chat.react');
            Route::get('/events/{event}/{slug}/chat/{message}/reactions', [EventChatController::class, 'reactions'])
                ->whereNumber('event')->where('slug', $slug)->whereNumber('message')
                ->name('events.chat.reactions');

            Route::get('/events/{event}/{slug}/ticket', [EventTicketController::class, 'show'])
                ->whereNumber('event')->where('slug', $slug)
                ->name('events.ticket');

            Route::post('/events/{event}/{slug}/ratings', [EventRatingController::class, 'store'])
                ->whereNumber('event')->where('slug', $slug)
                ->middleware('throttle:ratings')
                ->name('events.ratings.store');
        });

        Route::get('/chats', [ChatInboxController::class, 'index'])->name('chats.index');

        Route::get('/notifications', [NotificationInboxController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/read-all', [NotificationInboxController::class, 'markAllRead'])
            ->name('notifications.read-all');
        Route::post('/notifications/{notification}/read', [NotificationInboxController::class, 'markRead'])
            ->name('notifications.read');
    });

    Route::middleware('platform.admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        Route::get('/users', [UserModerationController::class, 'index'])->name('users.index');
        Route::get('/users/{user}/edit', [UserModerationController::class, 'edit'])
            ->whereNumber('user')
            ->name('users.edit');
        Route::put('/users/{user}', [UserModerationController::class, 'update'])
            ->whereNumber('user')
            ->name('users.update');
        Route::post('/users/{user}/deactivate', [UserModerationController::class, 'deactivate'])->name('users.deactivate');
        Route::post('/users/{user}/reactivate', [UserModerationController::class, 'reactivate'])->name('users.reactivate');

        Route::get('/identity', [IdentityDocumentModerationController::class, 'index'])->name('identity.index');
        Route::get('/identity/users/{user}', [IdentityDocumentModerationController::class, 'show'])->name('identity.show');
        Route::post('/identity/users/{user}/request-resubmit', [IdentityDocumentModerationController::class, 'requestResubmit'])
            ->whereNumber('user')
            ->name('identity.request-resubmit');
        Route::get('/identity/documents/{document}', [IdentityDocumentModerationController::class, 'download'])
            ->whereNumber('document')
            ->name('identity.download');

        Route::get('/events', [EventModerationController::class, 'index'])->name('events.index');
        Route::get('/events/calendar', [EventModerationController::class, 'calendar'])->name('events.calendar');
        Route::get('/events/{event}/edit', [EventModerationController::class, 'edit'])
            ->whereNumber('event')
            ->name('events.edit');
        Route::put('/events/{event}', [EventModerationController::class, 'update'])
            ->whereNumber('event')
            ->name('events.update');
        Route::delete('/events/{event}', [EventModerationController::class, 'destroy'])
            ->whereNumber('event')
            ->name('events.destroy');
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
        Route::delete('/cities/{city}', [AdminCityController::class, 'destroy'])->name('cities.destroy');

        Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
        Route::get('/categories/create', [AdminCategoryController::class, 'create'])->name('categories.create');
        Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
        Route::get('/categories/{category}/edit', [AdminCategoryController::class, 'edit'])->name('categories.edit');
        Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');
        Route::post('/categories/{category}/deactivate', [AdminCategoryController::class, 'deactivate'])->name('categories.deactivate');
        Route::post('/categories/{category}/activate', [AdminCategoryController::class, 'activate'])->name('categories.activate');
        Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('categories.destroy');
    });
    });
});

// Legacy /events/{id}/action → /events/{id}/{slug}/action (before show, so "edit" is not treated as a slug).
Route::get('/events/{event}/edit', function (Event $event) {
    return redirect()->to(event_route('events.edit', $event), 301);
})->whereNumber('event');
Route::get('/events/{event}/chat', function (Event $event) {
    return redirect()->to(event_route('events.chat', $event), 301);
})->whereNumber('event');
Route::get('/events/{event}/ticket', function (Event $event) {
    return redirect()->to(event_route('events.ticket', $event), 301);
})->whereNumber('event');
Route::get('/events/{event}/members', function (Event $event) {
    return redirect()->to(event_route('events.members', $event), 301);
})->whereNumber('event');
Route::get('/events/{event}/requests', function (Event $event) {
    return redirect()->to(event_route('events.requests', $event), 301);
})->whereNumber('event');

// Public event show — last among /events/{id}/… GET patterns.
Route::get('/events/{event}/{slug?}', [EventController::class, 'show'])
    ->whereNumber('event')
    ->where('slug', '^[A-Za-z0-9]+(?:-[A-Za-z0-9]+)*$')
    ->name('events.show');
