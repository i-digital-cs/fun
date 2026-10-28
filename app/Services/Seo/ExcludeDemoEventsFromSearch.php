<?php

namespace App\Services\Seo;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ExcludeDemoEventsFromSearch
{
    public const EXPECTED = 500;

    /**
     * Mark IDs 1–500 non-indexable only when every row is a confirmed demo seed.
     * Aborts with no update when the set is anything else.
     */
    public function __invoke(): int
    {
        DB::transaction(function (): void {
            $reasons = $this->rejectionReasons();
            if ($reasons !== []) {
                throw new RuntimeException(implode("\n", $reasons));
            }

            DB::table('events')->whereBetween('id', [1, self::EXPECTED])->update([
                'search_indexable' => false,
            ]);

            $stillIndexable = DB::table('events')
                ->whereBetween('id', [1, self::EXPECTED])
                ->where('search_indexable', true)
                ->count();

            if ($stillIndexable !== 0) {
                throw new RuntimeException('Demo exclusion did not apply to every matched event.');
            }
        });

        Cache::forget('seo.sitemap.xml');

        return self::EXPECTED;
    }

    /**
     * @return list<string>
     */
    public function rejectionReasons(): array
    {
        $reasons = EventFinancialLinks::unexpected(range(1, self::EXPECTED));

        $ids = DB::table('events as e')
            ->join('users as u', 'u.id', '=', 'e.created_by')
            ->whereBetween('e.id', [1, self::EXPECTED])
            ->where('e.description', 'like', 'Join us for %')
            ->where('u.email', 'like', '%@example.com')
            ->orderBy('e.id')
            ->pluck('e.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $expected = range(1, self::EXPECTED);

        if ($ids !== $expected) {
            $reasons[] = 'Expected exactly events 1–500 with description "Join us for …" and an @example.com creator. Matched '.count($ids).'.';
        }

        return $reasons;
    }
}
