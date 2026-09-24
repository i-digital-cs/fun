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
            str_contains($n, 'foot') || str_contains($n, 'كرة القدم') => '⚽',
            str_contains($n, 'basket') || str_contains($n, 'سلة') => '🏀',
            str_contains($n, 'padel') || str_contains($n, 'tennis') || str_contains($n, 'بادل') || str_contains($n, 'تنس') => '🎾',
            str_contains($n, 'run') || str_contains($n, 'جري') => '🏃',
            str_contains($n, 'chess') || str_contains($n, 'board') || str_contains($n, 'شطرنج') => '♟️',
            str_contains($n, 'cinema') || str_contains($n, 'film') || str_contains($n, 'movie') || str_contains($n, 'سينما') => '🎬',
            str_contains($n, 'social') || str_contains($n, 'اجتماع') => '🤝',
            str_contains($n, 'outdoor') || str_contains($n, 'خارج') => '🌳',
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
     *
     * @param  'card'|'detail'|'thumb'  $size
     */
    function event_cover_url(
        ?string $imageUrl,
        int $seed = 0,
        ?string $categorySlug = null,
        ?string $hint = null,
        string $size = 'card',
    ): string {
        if (filled($imageUrl)) {
            return $imageUrl;
        }

        $file = event_cover_filename($categorySlug, $hint, $seed);
        $base = pathinfo($file, PATHINFO_FILENAME);
        $width = match ($size) {
            'thumb' => 320,
            'detail' => 1280,
            default => 640,
        };
        $webp = "images/activities/{$base}-{$width}.webp";

        if (is_file(public_path($webp))) {
            return asset($webp);
        }

        return asset('images/activities/'.$file);
    }
}

if (! function_exists('event_image_card_path')) {
    /**
     * Sibling card variant path for an uploaded cover (`cover_uuid.webp` → `cover_uuid_640.webp`).
     */
    function event_image_card_path(string $path): ?string
    {
        if (preg_match('/^(.*\/cover_[0-9a-f-]+)\.(webp|jpe?g|png)$/i', $path, $matches) !== 1) {
            return null;
        }

        return $matches[1].'_640.'.$matches[2];
    }
}

if (! function_exists('event_cover_attrs')) {
    /**
     * Responsive cover attributes for Blade `<img>` / `<picture>`.
     *
     * @param  'card'|'detail'|'thumb'  $size
     * @return array{src: string, srcset: ?string, sizes: string, width: int, height: int, webp_srcset: ?string}
     */
    function event_cover_attrs(
        ?string $uploadedUrl,
        ?string $uploadedCardUrl,
        int $seed = 0,
        ?string $categorySlug = null,
        ?string $hint = null,
        string $size = 'card',
    ): array {
        $aspectW = 16;
        $aspectH = 9;

        if (filled($uploadedUrl)) {
            $src = match ($size) {
                'thumb', 'card' => $uploadedCardUrl ?: $uploadedUrl,
                default => $uploadedUrl,
            };

            $width = match ($size) {
                'thumb' => 84,
                'detail' => 1200,
                default => 640,
            };
            $height = (int) round($width * $aspectH / $aspectW);

            $srcset = null;
            if ($uploadedCardUrl && $uploadedCardUrl !== $uploadedUrl) {
                $srcset = $uploadedCardUrl.' 640w, '.$uploadedUrl.' 1280w';
            }

            $sizes = match ($size) {
                'thumb' => '42px',
                'detail' => '(max-width: 768px) 100vw, 896px',
                default => '(max-width: 640px) 100vw, 360px',
            };

            return [
                'src' => $src,
                'srcset' => $srcset,
                'sizes' => $sizes,
                'width' => $width,
                'height' => $height,
                'webp_srcset' => null,
            ];
        }

        $file = event_cover_filename($categorySlug, $hint, $seed);
        $base = pathinfo($file, PATHINFO_FILENAME);
        $widths = [320, 640, 1280];
        $srcsetParts = [];
        foreach ($widths as $w) {
            $path = "images/activities/{$base}-{$w}.webp";
            if (is_file(public_path($path))) {
                $srcsetParts[] = asset($path).' '.$w.'w';
            }
        }

        $defaultW = match ($size) {
            'thumb' => 320,
            'detail' => 1280,
            default => 640,
        };
        $srcPath = "images/activities/{$base}-{$defaultW}.webp";
        $src = is_file(public_path($srcPath))
            ? asset($srcPath)
            : asset('images/activities/'.$file);

        $displayW = match ($size) {
            'thumb' => 84,
            'detail' => 1200,
            default => 640,
        };

        return [
            'src' => $src,
            'srcset' => $srcsetParts !== [] ? implode(', ', $srcsetParts) : null,
            'sizes' => match ($size) {
                'thumb' => '42px',
                'detail' => '(max-width: 768px) 100vw, 896px',
                default => '(max-width: 640px) 100vw, 360px',
            },
            'width' => $displayW,
            'height' => (int) round($displayW * $aspectH / $aspectW),
            'webp_srcset' => $srcsetParts !== [] ? implode(', ', $srcsetParts) : null,
        ];
    }
}

if (! function_exists('event_cover_filename')) {
    function event_cover_filename(?string $categorySlug = null, ?string $hint = null, int $seed = 0): string
    {
        $haystack = mb_strtolower(trim(($categorySlug ?? '').' '.($hint ?? '')));

        // Title / keyword first so "FIFA" and "film night" beat a vague category.
        $matched = match (true) {
            str_contains($haystack, 'foot') || str_contains($haystack, 'soccer') || str_contains($haystack, 'fifa') || str_contains($haystack, 'kickabout') || str_contains($haystack, 'كرة القدم') || (str_contains($haystack, 'كرة') && ! str_contains($haystack, 'سلة')) => 'football.png',
            str_contains($haystack, 'basket') || str_contains($haystack, 'hoop') || str_contains($haystack, 'سلة') => 'basketball.png',
            str_contains($haystack, 'tennis') || str_contains($haystack, 'padel') || str_contains($haystack, 'تنس') || str_contains($haystack, 'بادل') => 'padel.png',
            str_contains($haystack, 'cinema') || str_contains($haystack, 'film') || str_contains($haystack, 'movie') || str_contains($haystack, 'سينما') => 'cinema.png',
            str_contains($haystack, 'playstation') || str_contains($haystack, 'console') || str_contains($haystack, 'gaming') || str_contains($haystack, 'esport') || str_contains($haystack, 'lan ') || str_contains($haystack, 'بلايستيشن') => 'gaming.png',
            str_contains($haystack, 'chess') || str_contains($haystack, 'board') || str_contains($haystack, 'catan') || str_contains($haystack, 'شطرنج') || str_contains($haystack, 'طاولة') => 'chess.png',
            str_contains($haystack, 'coffee') || str_contains($haystack, 'café') || str_contains($haystack, 'cafe') => 'coffee.png',
            str_contains($haystack, 'social') || str_contains($haystack, 'potluck') || str_contains($haystack, 'mixer') || str_contains($haystack, 'welcome') || str_contains($haystack, 'hangout') || str_contains($haystack, 'book club') || str_contains($haystack, 'اجتماع') => 'social.png',
            str_contains($haystack, 'cycl') || str_contains($haystack, 'bike') || str_contains($haystack, 'hike') || str_contains($haystack, 'دراج') => 'cycling.png',
            str_contains($haystack, 'outdoor') || str_contains($haystack, 'picnic') || str_contains($haystack, 'yoga') || str_contains($haystack, 'خارج') => 'cycling.png',
            // "run" last among sports so "basketball run" already matched basketball
            str_contains($haystack, 'run') || str_contains($haystack, 'jog') || str_contains($haystack, '5k') || str_contains($haystack, 'جري') => 'running.png',
            str_contains($haystack, 'game') || str_contains($haystack, 'ألعاب') => 'gaming.png',
            default => null,
        };

        if ($matched !== null) {
            return $matched;
        }

        $bySlug = [
            'football' => 'football.png',
            'basketball' => 'basketball.png',
            'tennis' => 'padel.png',
            'running' => 'running.png',
            'outdoor' => 'cycling.png',
            'board-games' => 'chess.png',
            'gaming' => 'gaming.png',
            'playstation' => 'gaming.png',
            'cinema' => 'cinema.png',
            'social' => 'social.png',
        ];

        if ($categorySlug && isset($bySlug[$categorySlug])) {
            return $bySlug[$categorySlug];
        }

        $fallback = [
            'football.png', 'padel.png', 'running.png', 'chess.png', 'cycling.png',
            'cinema.png', 'social.png', 'basketball.png', 'gaming.png', 'coffee.png',
        ];

        return $fallback[abs($seed) % count($fallback)];
    }
}

if (! function_exists('event_show_url')) {
    /**
     * Canonical public event URL: /events/{id}/{slug}.
     */
    function event_show_url(\App\Models\Event $event): string
    {
        return $event->showUrl();
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
