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

if (! function_exists('site_logo_url')) {
    function site_logo_url(): ?string
    {
        try {
            return app(SiteSettingsService::class)->logoUrl();
        } catch (Throwable) {
            return null;
        }
    }
}

if (! function_exists('category_emoji')) {
    function category_emoji(?string $name): string
    {
        $n = mb_strtolower((string) $name);

        return match (true) {
            str_contains($n, 'foot') || str_contains($n, 'كرة') => '⚽',
            str_contains($n, 'padel') || str_contains($n, 'tennis') || str_contains($n, 'بادل') || str_contains($n, 'تنس') => '🎾',
            str_contains($n, 'run') || str_contains($n, 'جري') => '🏃',
            str_contains($n, 'chess') || str_contains($n, 'board') || str_contains($n, 'شطرنج') => '♟️',
            str_contains($n, 'book') || str_contains($n, 'كتاب') => '📚',
            str_contains($n, 'cycl') || str_contains($n, 'دراج') => '🚴',
            str_contains($n, 'play') || str_contains($n, 'game') || str_contains($n, 'لعب') => '🎮',
            default => '✨',
        };
    }
}

if (! function_exists('event_cover_url')) {
    function event_cover_url(?string $imageUrl, int $seed = 0): string
    {
        if (filled($imageUrl)) {
            return $imageUrl;
        }

        $covers = [
            asset('images/activities/football.png'),
            asset('images/activities/padel.png'),
            asset('images/activities/running.png'),
            asset('images/activities/chess.png'),
            asset('images/activities/cycling.png'),
        ];

        return $covers[abs($seed) % count($covers)];
    }
}

if (! function_exists('user_initials')) {
    function user_initials(?string $name): string
    {
        if (! filled($name)) {
            return '?';
        }

        return collect(preg_split('/\s+/', trim($name)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }
}
