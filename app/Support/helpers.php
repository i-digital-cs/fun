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
    /**
     * Uploaded cover, or a stock activity image matched to category / title
     * (not a random pick by event id).
     */
    function event_cover_url(
        ?string $imageUrl,
        int $seed = 0,
        ?string $categorySlug = null,
        ?string $hint = null,
    ): string {
        if (filled($imageUrl)) {
            return $imageUrl;
        }

        $file = event_cover_filename($categorySlug, $hint, $seed);

        return asset('images/activities/'.$file);
    }
}

if (! function_exists('event_cover_filename')) {
    function event_cover_filename(?string $categorySlug = null, ?string $hint = null, int $seed = 0): string
    {
        $haystack = mb_strtolower(trim(($categorySlug ?? '').' '.($hint ?? '')));

        $matched = match (true) {
            str_contains($haystack, 'foot') || str_contains($haystack, 'soccer') || str_contains($haystack, 'fifa') || str_contains($haystack, 'كرة') => 'football.png',
            str_contains($haystack, 'basket') || str_contains($haystack, 'hoop') || str_contains($haystack, 'سلة') => 'football.png',
            str_contains($haystack, 'tennis') || str_contains($haystack, 'padel') || str_contains($haystack, 'تنس') || str_contains($haystack, 'بادل') => 'padel.png',
            str_contains($haystack, 'run') || str_contains($haystack, 'jog') || str_contains($haystack, '5k') || str_contains($haystack, 'جري') => 'running.png',
            str_contains($haystack, 'cycl') || str_contains($haystack, 'bike') || str_contains($haystack, 'outdoor') || str_contains($haystack, 'hike') || str_contains($haystack, 'picnic') || str_contains($haystack, 'دراج') => 'cycling.png',
            str_contains($haystack, 'chess') || str_contains($haystack, 'board') || str_contains($haystack, 'catan') || str_contains($haystack, 'شطرنج') || str_contains($haystack, 'طاولة') => 'chess.png',
            str_contains($haystack, 'playstation') || str_contains($haystack, 'console') || str_contains($haystack, 'gaming') || str_contains($haystack, 'بلايستيشن') => 'padel.png',
            str_contains($haystack, 'cinema') || str_contains($haystack, 'film') || str_contains($haystack, 'movie') || str_contains($haystack, 'سينما') => 'cycling.png',
            str_contains($haystack, 'social') || str_contains($haystack, 'coffee') || str_contains($haystack, 'اجتماع') => 'padel.png',
            default => null,
        };

        if ($matched !== null) {
            return $matched;
        }

        $bySlug = [
            'football' => 'football.png',
            'basketball' => 'football.png',
            'tennis' => 'padel.png',
            'running' => 'running.png',
            'outdoor' => 'cycling.png',
            'board-games' => 'chess.png',
            'gaming' => 'chess.png',
            'playstation' => 'padel.png',
            'cinema' => 'cycling.png',
            'social' => 'padel.png',
        ];

        if ($categorySlug && isset($bySlug[$categorySlug])) {
            return $bySlug[$categorySlug];
        }

        $fallback = ['football.png', 'padel.png', 'running.png', 'chess.png', 'cycling.png'];

        return $fallback[abs($seed) % count($fallback)];
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
