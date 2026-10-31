<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Middleware\LogApiRequests;
use App\Http\Middleware\EnforceCanonicalUrl;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetLocaleFromPath;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(EnforceCanonicalUrl::class);

        $middleware->web(append: [
            SetLocaleFromPath::class,
            SetLocale::class,
        ]);

        $middleware->api(prepend: [
            SetLocale::class,
            LogApiRequests::class,
        ]);

        // Same-origin browser session cookies authenticate /api/* (chat media <img>/<audio>).
        // Flutter continues to use Bearer tokens; stateful middleware only activates for stateful domains.
        $middleware->statefulApi();

        $middleware->alias([
            'account.active' => EnsureAccountIsActive::class,
            'platform.admin' => EnsurePlatformAdmin::class,
            'verified.email' => EnsureEmailIsVerified::class,
            'event.slug' => \App\Http\Middleware\EnsureEventSlugMatches::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
