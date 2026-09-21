<?php

namespace App\Http\Controllers\Web\Profile;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateLocationPreferenceRequest;
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

        return back()->with('status', __('location.preferences_saved'));
    }
}
