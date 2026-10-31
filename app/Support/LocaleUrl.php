<?php

namespace App\Support;

use App\Models\Event;

class LocaleUrl
{
    /**
     * @return list<string>
     */
    public static function supported(): array
    {
        return ['en', 'ar'];
    }

    /**
     * Absolute URL for a named public page in a locale.
     * English uses legacy bare paths (canonical). Arabic uses /ar/...
     */
    public static function page(string $logical, string $locale = 'en', mixed ...$params): string
    {
        $locale = in_array($locale, self::supported(), true) ? $locale : 'en';

        return match ($logical) {
            'home' => $locale === 'ar'
                ? Seo::absoluteUrl('/ar')
                : route('home'),
            'events.index' => $locale === 'ar'
                ? Seo::absoluteUrl('/ar/events')
                : route('events.index'),
            'safety' => $locale === 'ar'
                ? Seo::absoluteUrl('/ar/safety')
                : route('safety.guidelines'),
            'privacy' => $locale === 'ar'
                ? Seo::absoluteUrl('/ar/privacy-policy')
                : route('legal.privacy'),
            'terms' => $locale === 'ar'
                ? Seo::absoluteUrl('/ar/terms')
                : route('legal.terms'),
            'support' => $locale === 'ar'
                ? Seo::absoluteUrl('/ar/support')
                : route('legal.support'),
            default => route('home'),
        };
    }

    public static function event(Event $event, string $locale = 'en'): string
    {
        $locale = in_array($locale, self::supported(), true) ? $locale : 'en';
        $slug = $event->slugForLocale($locale);
        $path = '/events/'.$event->getKey().'/'.$slug;

        if ($locale === 'ar') {
            return Seo::absoluteUrl('/ar'.$path);
        }

        return Seo::absoluteUrl($path);
    }

    /**
     * Reciprocal hreflang map for a logical page.
     *
     * @return array{ar: string, en: string, x-default: string}
     */
    public static function alternatesForPage(string $logical): array
    {
        $en = self::page($logical, 'en');
        $ar = self::page($logical, 'ar');

        return [
            'ar' => $ar,
            'en' => $en,
            'x-default' => $en,
        ];
    }

    /**
     * @return array{ar: string, en: string, x-default: string}
     */
    public static function alternatesForEvent(Event $event): array
    {
        $en = self::event($event, 'en');
        $ar = self::event($event, 'ar');

        return [
            'ar' => $ar,
            'en' => $en,
            'x-default' => $en,
        ];
    }
}
