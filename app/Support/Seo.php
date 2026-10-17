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
     *     jsonLd: array<string, mixed>|null,
     *     absoluteTitle: bool
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
        bool $absoluteTitle = false,
    ): array {
        return [
            'title' => $title,
            'description' => $description,
            'image' => $image,
            'url' => $url,
            'type' => $type,
            'robots' => $robots,
            'jsonLd' => $jsonLd,
            'absoluteTitle' => $absoluteTitle,
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
    public static function forHome(?string $highlightImage = null): array
    {
        $brand = site_display_name();
        $description = __('design.home_meta_description');
        $homeUrl = route('home');

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    'name' => $brand,
                    'url' => $homeUrl,
                    'description' => $description,
                    'logo' => self::absoluteUrl(site_logo_url() ?: asset('logo.svg')),
                    'contactPoint' => [
                        '@type' => 'ContactPoint',
                        'contactType' => 'customer support',
                        'email' => __('privacy.contact_email'),
                        'url' => route('legal.support'),
                    ],
                ],
                [
                    '@type' => 'WebSite',
                    'name' => $brand,
                    'url' => $homeUrl,
                    'description' => $description,
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
            title: __('design.home_document_title'),
            description: $description,
            image: $highlightImage ?: (site_logo_url() ?: asset('logo.svg')),
            url: $homeUrl,
            jsonLd: $jsonLd,
            absoluteTitle: true,
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
        $description = self::eventMetaDescription($event);
        $schemaDescription = filled($event->description)
            ? $event->description
            : $description;
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

        $locality = $event->city?->localizedName();
        if (filled($locality)) {
            $location['address'] = [
                '@type' => 'PostalAddress',
                'addressLocality' => $locality,
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
            'description' => self::truncateDescription($schemaDescription, 500),
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

        $public = in_array($event->status, [EventStatus::Published, EventStatus::Completed], true);
        $searchable = $event->search_indexable !== false;

        return self::make(
            title: $title,
            description: $description,
            image: $image,
            url: $url,
            type: 'article',
            robots: match (true) {
                $public && ! $searchable => 'noindex,follow',
                $public => 'index,follow',
                default => 'noindex,nofollow',
            },
            jsonLd: $jsonLd,
        );
    }

    /**
     * Meta description uses the organizer's text plus the stored city and date.
     * It does not invent a place or a time that is not on the event.
     */
    private static function eventMetaDescription(Event $event): string
    {
        $suffixParts = array_values(array_filter([
            $event->city?->localizedName(),
            $event->starts_at?->timezone((string) config('app.timezone'))->translatedFormat('j M Y'),
        ], fn ($value) => filled($value)));
        $suffix = $suffixParts === [] ? '' : ' · '.implode(' · ', $suffixParts);

        $body = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $event->description)) ?? '');
        if ($body === '') {
            $body = filled($event->title) ? trim((string) $event->title) : (string) __('events.discover_subtitle');
        }

        $limit = 155;
        if ($suffix !== '' && mb_strlen($suffix) < $limit - 20) {
            return trim(Str::limit($body, $limit - mb_strlen($suffix), '').$suffix);
        }

        return Str::limit($body, $limit, '');
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

        $indexable = (bool) config('seo.public_profiles_indexable', true);
        $jsonLd = null;
        if ($indexable) {
            $jsonLd = [
                '@context' => 'https://schema.org',
                '@type' => 'Person',
                'name' => $name,
                'url' => $url,
            ];
            if (filled($image)) {
                $jsonLd['image'] = self::absoluteUrl($image);
            }
        }

        return self::make(
            title: $name.(filled($profile['public_id'] ?? null) ? ' · '.$profile['public_id'] : ''),
            description: $description,
            image: $image ?: (site_logo_url() ?: asset('logo.svg')),
            url: $url,
            type: 'profile',
            robots: $indexable ? 'index,follow' : 'noindex,follow',
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
    public static function forPrivacy(): array
    {
        return self::make(
            title: __('privacy.title'),
            description: __('privacy.meta_description'),
            image: site_logo_url() ?: asset('logo.svg'),
            url: route('legal.privacy'),
            robots: 'index,follow',
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
    public static function forTerms(): array
    {
        return self::make(
            title: __('terms.title'),
            description: __('terms.meta_description'),
            image: site_logo_url() ?: asset('logo.svg'),
            url: route('legal.terms'),
            robots: 'index,follow',
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
    public static function forSupport(): array
    {
        return self::make(
            title: __('support.title'),
            description: __('support.meta_description'),
            image: site_logo_url() ?: asset('logo.svg'),
            url: route('legal.support'),
            robots: 'index,follow',
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
    public static function forAccountDeletion(): array
    {
        return self::make(
            title: __('account_deletion.title'),
            description: __('account_deletion.meta_description'),
            image: site_logo_url() ?: asset('logo.svg'),
            url: route('legal.account-deletion'),
            robots: 'index,follow',
        );
    }
}
