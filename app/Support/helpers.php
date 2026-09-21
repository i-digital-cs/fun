<?php

use App\Services\Admin\SiteSettingsService;

if (! function_exists('site_display_name')) {
    function site_display_name(): string
    {
        try {
            return app(SiteSettingsService::class)->displayName();
        } catch (Throwable) {
            return (string) config('app.name', 'Community Platform');
        }
    }
}
