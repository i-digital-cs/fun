<?php

namespace App\Http\Controllers\Web;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\Seo\LandingPageCatalog;
use App\Support\LocaleUrl;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $xml = Cache::remember('seo.sitemap.xml', 3600, function (): string {
            $urls = [
                $this->localizedStatic(LocaleUrl::alternatesForPage('home'), 'daily', '1.0'),
                $this->localizedStatic(LocaleUrl::alternatesForPage('events.index'), 'hourly', '0.9'),
                $this->localizedStatic(LocaleUrl::alternatesForPage('safety'), 'monthly', '0.4'),
                $this->localizedStatic(LocaleUrl::alternatesForPage('privacy'), 'monthly', '0.4'),
                $this->localizedStatic(LocaleUrl::alternatesForPage('terms'), 'monthly', '0.4'),
                $this->localizedStatic(LocaleUrl::alternatesForPage('support'), 'monthly', '0.3'),
                $this->staticUrl(route('legal.account-deletion'), 'monthly', '0.5'),
            ];

            $catalog = app(LandingPageCatalog::class);
            foreach ($catalog->eligibleCities() as $city) {
                $en = route('events.city', $city);
                $ar = url('/ar/events/in/'.$city->slug);
                $urls[] = $this->localizedStatic([
                    'en' => $en,
                    'ar' => $ar,
                    'x-default' => $en,
                ], 'daily', '0.7');
            }
            foreach ($catalog->eligibleCategories() as $category) {
                $en = route('events.activity', $category);
                $ar = url('/ar/events/activity/'.$category->slug);
                $urls[] = $this->localizedStatic([
                    'en' => $en,
                    'ar' => $ar,
                    'x-default' => $en,
                ], 'daily', '0.7');
            }

            $eventColumns = ['id', 'slug', 'title', 'updated_at'];
            if (\Illuminate\Support\Facades\Schema::hasColumn('events', 'slug_ar')) {
                $eventColumns[] = 'slug_ar';
            }

            Event::query()
                ->where('search_indexable', true)
                ->whereIn('status', [EventStatus::Published, EventStatus::Completed])
                ->orderByDesc('updated_at')
                ->limit(5000)
                ->get($eventColumns)
                ->each(function (Event $event) use (&$urls): void {
                    $alts = LocaleUrl::alternatesForEvent($event);
                    $row = $this->localizedStatic($alts, 'daily', '0.8');
                    $lastmod = $event->updated_at?->toAtomString();
                    if (is_string($lastmod) && $lastmod !== '') {
                        $row['lastmod'] = $lastmod;
                    }
                    $urls[] = $row;
                });

            return view('seo.sitemap', ['urls' => $urls])->render();
        });

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * @param  array{ar?: string, en?: string, x-default?: string}  $alternates
     * @return array{loc: string, changefreq: string, priority: string, alternates?: array<string, string>}
     */
    private function localizedStatic(array $alternates, string $changefreq, string $priority): array
    {
        $loc = $alternates['en'] ?? $alternates['x-default'] ?? reset($alternates);

        return [
            'loc' => $loc,
            'changefreq' => $changefreq,
            'priority' => $priority,
            'alternates' => array_filter([
                'en' => $alternates['en'] ?? null,
                'ar' => $alternates['ar'] ?? null,
                'x-default' => $alternates['x-default'] ?? ($alternates['en'] ?? null),
            ]),
        ];
    }

    /**
     * @return array{loc: string, changefreq: string, priority: string}
     */
    private function staticUrl(string $loc, string $changefreq, string $priority): array
    {
        return [
            'loc' => $loc,
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];
    }
}
