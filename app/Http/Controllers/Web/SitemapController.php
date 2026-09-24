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
                [
                    'loc' => route('home'),
                    'changefreq' => 'daily',
                    'priority' => '1.0',
                    'lastmod' => now()->toAtomString(),
                ],
                [
                    'loc' => route('events.index'),
                    'changefreq' => 'hourly',
                    'priority' => '0.9',
                    'lastmod' => now()->toAtomString(),
                ],
                [
                    'loc' => route('safety.guidelines'),
                    'changefreq' => 'monthly',
                    'priority' => '0.4',
                    'lastmod' => now()->toAtomString(),
                ],
            ];

            Event::query()
                ->whereIn('status', [EventStatus::Published, EventStatus::Completed])
                ->orderByDesc('updated_at')
                ->limit(5000)
                ->get(['id', 'updated_at'])
                ->each(function (Event $event) use (&$urls): void {
                    $urls[] = [
                        'loc' => route('events.show', $event),
                        'changefreq' => 'daily',
                        'priority' => '0.8',
                        'lastmod' => $event->updated_at?->toAtomString(),
                    ];
                });

            return view('seo.sitemap', ['urls' => $urls])->render();
        });

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
