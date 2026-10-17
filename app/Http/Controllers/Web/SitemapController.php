<?php

namespace App\Http\Controllers\Web;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $xml = Cache::remember('seo.sitemap.xml', 3600, function (): string {
            $urls = [
                $this->staticUrl(route('home'), 'daily', '1.0'),
                $this->staticUrl(route('events.index'), 'hourly', '0.9'),
                $this->staticUrl(route('safety.guidelines'), 'monthly', '0.4'),
                $this->staticUrl(route('legal.privacy'), 'monthly', '0.4'),
                $this->staticUrl(route('legal.terms'), 'monthly', '0.4'),
                $this->staticUrl(route('legal.support'), 'monthly', '0.3'),
                $this->staticUrl(route('legal.account-deletion'), 'monthly', '0.5'),
            ];

            Event::query()
                ->where('search_indexable', true)
                ->whereIn('status', [EventStatus::Published, EventStatus::Completed])
                ->orderByDesc('updated_at')
                ->limit(5000)
                ->get(['id', 'slug', 'title', 'updated_at'])
                ->each(function (Event $event) use (&$urls): void {
                    $row = [
                        'loc' => event_show_url($event),
                        'changefreq' => 'daily',
                        'priority' => '0.8',
                    ];
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
     * Static routes have no content row with a reliable modification time.
     * Omit lastmod rather than stamping the generation time.
     *
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
