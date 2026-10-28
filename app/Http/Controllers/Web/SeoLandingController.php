<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\City;
use App\Services\Events\ResolveEventJoinActionService;
use App\Services\Seo\LandingPageCatalog;
use App\Support\Seo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeoLandingController extends Controller
{
    public function city(
        Request $request,
        City $city,
        LandingPageCatalog $catalog,
        ResolveEventJoinActionService $joinActions,
    ): View {
        abort_unless($catalog->cityIsEligible($city), 404);

        $events = $this->events($catalog, 'city_id', $city->id);
        $otherName = app()->getLocale() === 'ar' ? $city->name_en : $city->name_ar;

        return $this->render(
            $request,
            $events,
            $joinActions,
            __('events.city_landing_title', ['city' => $city->localizedName()]),
            __('events.city_landing_description', [
                'city' => $city->localizedName(),
                'other' => $otherName,
                'count' => $catalog->qualifyingEvents()->where('city_id', $city->id)->count(),
            ]),
            route('events.city', $city),
        );
    }

    public function activity(
        Request $request,
        Category $category,
        LandingPageCatalog $catalog,
        ResolveEventJoinActionService $joinActions,
    ): View {
        abort_unless($catalog->categoryIsEligible($category), 404);

        $events = $this->events($catalog, 'category_id', $category->id);
        $otherName = app()->getLocale() === 'ar' ? $category->name_en : $category->name_ar;

        return $this->render(
            $request,
            $events,
            $joinActions,
            __('events.activity_landing_title', ['activity' => $category->localizedName()]),
            __('events.activity_landing_description', [
                'activity' => $category->localizedName(),
                'other' => $otherName,
                'count' => $catalog->qualifyingEvents()->where('category_id', $category->id)->count(),
            ]),
            route('events.activity', $category),
        );
    }

    /**
     * @return LengthAwarePaginator<int, \App\Models\Event>
     */
    private function events(LandingPageCatalog $catalog, string $column, int $id): LengthAwarePaginator
    {
        return $catalog->qualifyingEvents()
            ->with(['category', 'city', 'creator'])
            ->withCount('members')
            ->where($column, $id)
            ->orderByDesc('starts_at')
            ->paginate(12);
    }

    /**
     * @param  LengthAwarePaginator<int, \App\Models\Event>  $events
     */
    private function render(
        Request $request,
        LengthAwarePaginator $events,
        ResolveEventJoinActionService $joinActions,
        string $title,
        string $description,
        string $url,
    ): View {
        if ($events->isEmpty()) {
            abort(404);
        }

        $items = [];
        foreach ($events as $index => $event) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $events->firstItem() + $index,
                'name' => $event->title,
                'url' => event_show_url($event),
            ];
        }

        return view('events.landing', [
            'events' => $events,
            'joinActionsById' => $joinActions->resolveMany($request->user(), $events->getCollection()),
            'favoritedIds' => [],
            'landingTitle' => $title,
            'landingDescription' => $description,
            'categories' => Category::query()->active()->orderBy('sort_order')->orderBy('name_en')->get(),
            'seo' => Seo::forLanding($title, $description, $url, (int) $events->currentPage(), $items),
        ]);
    }
}
