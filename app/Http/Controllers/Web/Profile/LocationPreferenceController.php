<?php

namespace App\Http\Controllers\Web\Profile;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateLocationPreferenceRequest;
use App\Models\Category;
use App\Models\City;
use App\Services\Location\UpdateUserLocationPreferenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LocationPreferenceController extends Controller
{
    public function edit(): View
    {
        $cities = City::query()->active()->orderBy('name_en')->get();
        $distances = config('location.distances_km');

        return view('profile.location', [
            'cities' => $cities,
            'distances' => $distances,
            'user' => auth()->user()->load('city'),
            'categories' => Category::query()->active()->orderBy('sort_order')->orderBy('name_en')->get(),
        ]);
    }

    public function update(
        UpdateLocationPreferenceRequest $request,
        UpdateUserLocationPreferenceService $service,
    ): RedirectResponse {
        try {
            $service->update($request->user(), $request->validated());
        } catch (DomainException $exception) {
            return back()->withErrors(['city_id' => $exception->getMessage()]);
        }

        $redirectTo = $request->input('redirect_to');
        if (is_string($redirectTo) && $redirectTo !== '') {
            $path = parse_url($redirectTo, PHP_URL_PATH) ?: '';
            $query = parse_url($redirectTo, PHP_URL_QUERY);
            // Same-origin relative redirects only.
            if (str_starts_with($path, '/') && ! str_starts_with($path, '//')) {
                $target = $path.($query ? '?'.$query : '');

                return redirect($target)->with('status', __('location.preferences_saved'));
            }
        }

        return back()->with('status', __('location.preferences_saved'));
    }
}
