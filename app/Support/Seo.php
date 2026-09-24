<?php

namespace App\Support;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Support\Str;

class Seo
{
    /**
     * @param  array<string, mixed>|null  $jsonLd
     * @return array{
     *     title: ?string,
     *     description: ?string,
     *     image: ?string,
     *     url: ?string,
     *     type: string,
     *     robots: string,
     *     jsonLd: array<string, mixed>|null
     * }
     */
    public static function make(
        ?string $title = null,
        ?string $description = null,
        ?string $image = null,
        ?string $url = null,
        string $type = 'website',
        string $robots = 'index,follow',
        ?array $jsonLd = null,
    ): array {
        return [
            'title' => $title,
            'description' => $description,
            'image' => $image,
            'url' => $url,
            'type' => $type,
            'robots' => $robots,
            'jsonLd' => $jsonLd,
        ];
    }

    /**
     * @return array{
     *     title: ?string,
     *     description: ?string,
     *     image: ?string,
     *     url: ?string,
     *     type: string,
     *     robots: string,
     *     jsonLd: array<string, mixed>|null
     * }
     */
    public static function noindex(?string $title = null): array
    {
        return self::make(
            title: $title,
            robots: 'noindex,nofollow',
        );
    }

    public static function absoluteUrl(?string $pathOrUrl): string
    {
        if ($pathOrUrl === null || $pathOrUrl === '') {
            return url('/');
        }

        if (str_starts_with($pathOrUrl, 'http://') || str_starts_with($pathOrUrl, 'https://')) {
            return $pathOrUrl;
        }

        return url($pathOrUrl);
    }

    public static function truncateDescription(?string $text, int $limit = 155): string
    {
        $clean = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $text)) ?? '');

        return Str::limit($clean, $limit, '');
    }

    /**
     * @return array{
     *     title: ?string,
     *     description: ?string,
     *     image: ?string,
     *     url: ?string,
     *     type: string,
     *     robots: string,
     *     jsonLd: array<string, mixed>|null
     * }
     */
    public static function forHome(?string $cityName = null, ?string $highlightImage = null): array
    {
        $brand = site_display_name();
        $description = $cityName
            ? __('design.welcome_sub_design', ['city' => $cityName])
            : __('design.welcome_sub_generic');

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    'name' => $brand,
                    'url' => route('home'),
                    'logo' => self::absoluteUrl(site_logo_url() ?: asset('logo.svg')),
                ],
                [
                    '@type' => 'WebSite',
                    'name' => $brand,
                    'url' => route('home'),
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => [
                            '@type' => 'EntryPoint',
                            'urlTemplate' => route('events.index').'?q={search_term_string}',
                        ],
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
            ],
        ];

        return self::make(
            title: __('design.welcome_guest'),
            description: $description,
            image: $highlightImage ?: (site_logo_url() ?: asset('logo.svg')),
            url: route('home'),
            jsonLd: $jsonLd,
        );
    }

    /**
     * @return array{
     *     title: ?string,
     *     description: ?string,
     *     image: ?string,
     *     url: ?string,
     *     type: string,
     *     robots: string,
     *     jsonLd: array<string, mixed>|null
     * }
     */
    public static function forDiscover(int $page = 1, bool $hasFilters = false): array
    {
        $robots = ($page > 1 || $hasFilters) ? 'noindex,follow' : 'index,follow';

        return self::make(
            title: __('events.discover'),
            description: __('events.discover_subtitle'),
            image: site_logo_url() ?: asset('logo.svg'),
            url: route('events.index'),
            robots: $robots,
        );
    }

    /**
     * @return array{
     *     title: ?string,
     *     description: ?string,
     *     image: ?string,
     *     url: ?string,
     *     type: string,
     *     robots: string,
     *     jsonLd: array<string, mixed>|null
     * }
     */
    public static function forEvent(Event $event): array
    {
        $title = filled($event->title) ? $event->title : __('events.view_event');
        $description = filled($event->description)
            ? $event->description
            : __('events.discover_subtitle');
        $url = event_show_url($event);
        $image = $event->coverUrl('detail');

        $location = [
            '@type' => 'Place',
            'name' => $event->locationLabel() !== ''
                ? $event->locationLabel()
                : site_display_name(),
        ];

        if ($event->hasPlacePin()) {
            $location['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => $event->latitude,
                'longitude' => $event->longitude,
            ];
        }

        $status = match ($event->status) {
            EventStatus::Cancelled => 'https://schema.org/EventCancelled',
            EventStatus::Completed => 'https://schema.org/EventScheduled',
            default => 'https://schema.org/EventScheduled',
        };

        $eventSchema = [
            '@type' => 'Event',
            'name' => $title,
            'description' => self::truncateDescription($description, 500),
            'image' => [self::absoluteUrl($image)],
            'url' => $url,
            'eventStatus' => $status,
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'location' => $location,
        ];

        if ($event->starts_at !== null) {
            $eventSchema['startDate'] = $event->starts_at->toIso8601String();
        }
        if ($event->ends_at !== null) {
            $eventSchema['endDate'] = $event->ends_at->toIso8601String();
        }

        if ($event->creator !== null) {
            $organizer = [
                '@type' => 'Person',
                'name' => $event->creator->name,
            ];
            if (filled($event->creator->public_id)) {
                $organizer['url'] = route('users.public.show', $event->creator->public_id);
            }
            $eventSchema['organizer'] = $organizer;
        }

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@graph' => [
                $eventSchema,
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        [
                            '@type' => 'ListItem',
                            'position' => 1,
                            'name' => site_display_name(),
                            'item' => route('home'),
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 2,
                            'name' => __('events.discover'),
                            'item' => route('events.index'),
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 3,
                            'name' => $title,
                            'item' => $url,
                        ],
                    ],
                ],
            ],
        ];

        $indexable = in_array($event->status, [EventStatus::Published, EventStatus::Completed], true);

        return self::make(
            title: $title,
            description: $description,
            image: $image,
            url: $url,
            type: 'article',
            robots: $indexable ? 'index,follow' : 'noindex,nofollow',
            jsonLd: $jsonLd,
        );
    }

    /**
     * @param  array{name: string, public_id?: string|null, profile_image_path?: string|null}  $profile
     * @return array{
     *     title: ?string,
     *     description: ?string,
     *     image: ?string,
     *     url: ?string,
     *     type: string,
     *     robots: string,
     *     jsonLd: array<string, mixed>|null
     * }
     */
    public static function forPublicProfile(array $profile, string $publicId): array
    {
        $name = $profile['name'];
        $url = route('users.public.show', $publicId);
        $image = $profile['profile_image_path'] ?? null;
        $description = __('profile.public_profile').' · '.$name
            .(filled($profile['public_id'] ?? null) ? ' ('.$profile['public_id'].')' : '');

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $name,
            'url' => $url,
        ];
        if (filled($image)) {
            $jsonLd['image'] = self::absoluteUrl($image);
        }

        return self::make(
            title: $name.(filled($profile['public_id'] ?? null) ? ' · '.$profile['public_id'] : ''),
            description: $description,
            image: $image ?: (site_logo_url() ?: asset('logo.svg')),
            url: $url,
            type: 'profile',
            robots: 'index,follow',
            jsonLd: $jsonLd,
        );
    }

    /**
     * @return array{
     *     title: ?string,
     *     description: ?string,
     *     image: ?string,
     *     url: ?string,
     *     type: string,
     *     robots: string,
     *     jsonLd: array<string, mixed>|null
     * }
     */
    public static function forSafety(): array
    {
        return self::make(
            title: __('safety.guidelines_title'),
            description: __('safety.guidelines_intro'),
            image: site_logo_url() ?: asset('logo.svg'),
            url: route('safety.guidelines'),
            robots: 'index,follow',
        );
    }
}
