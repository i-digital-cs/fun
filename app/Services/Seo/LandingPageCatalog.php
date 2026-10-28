<?php

namespace App\Services\Seo;

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Indexable city and activity pages.
 * A page exists only when it has enough genuine events.
 * Demo rows, cancelled rows, and placeholder descriptions do not count.
 */
class LandingPageCatalog
{
    public const MINIMUM_EVENTS = 3;

    public const MINIMUM_DESCRIPTION_CHARS = 40;

    /**
     * @return Builder<Event>
     */
    public function qualifyingEvents(): Builder
    {
        $length = DB::connection()->getDriverName() === 'sqlite' ? 'length' : 'CHAR_LENGTH';

        return Event::query()
            ->where('search_indexable', true)
            ->whereIn('status', [EventStatus::Published, EventStatus::Completed])
            ->whereNotNull('description')
            ->where('description', '!=', 'Description')
            ->where('description', 'not like', 'Join us for %')
            ->whereRaw($length.'(trim(description)) >= ?', [self::MINIMUM_DESCRIPTION_CHARS]);
    }

    public function cityIsEligible(City $city): bool
    {
        if (! $city->is_active) {
            return false;
        }

        return $this->qualifyingEvents()->where('city_id', $city->id)->count() >= self::MINIMUM_EVENTS;
    }

    public function categoryIsEligible(Category $category): bool
    {
        if (! $category->is_active) {
            return false;
        }

        return $this->qualifyingEvents()->where('category_id', $category->id)->count() >= self::MINIMUM_EVENTS;
    }

    /**
     * @return Collection<int, City>
     */
    public function eligibleCities(): Collection
    {
        return City::query()
            ->active()
            ->orderBy('name_en')
            ->get()
            ->filter(fn (City $city) => $this->cityIsEligible($city))
            ->values();
    }

    /**
     * @return Collection<int, Category>
     */
    public function eligibleCategories(): Collection
    {
        return Category::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name_en')
            ->get()
            ->filter(fn (Category $category) => $this->categoryIsEligible($category))
            ->values();
    }
}
