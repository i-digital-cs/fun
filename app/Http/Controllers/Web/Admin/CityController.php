<?php

namespace App\Http\Controllers\Web\Admin;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCityRequest;
use App\Http\Requests\Admin\UpdateCityRequest;
use App\Models\City;
use App\Services\Location\ManageCityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CityController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', City::class);

        $cities = City::query()->orderBy('name_en')->paginate(30);

        return view('admin.cities.index', compact('cities'));
    }

    public function create(): View
    {
        $this->authorize('create', City::class);

        return view('admin.cities.create');
    }

    public function store(StoreCityRequest $request, ManageCityService $service): RedirectResponse
    {
        $this->authorize('create', City::class);

        $service->create($request->validated());

        return redirect()->route('admin.cities.index')
            ->with('status', __('location.city_created'));
    }

    public function edit(City $city): View
    {
        $this->authorize('update', $city);

        return view('admin.cities.edit', compact('city'));
    }

    public function update(UpdateCityRequest $request, City $city, ManageCityService $service): RedirectResponse
    {
        $this->authorize('update', $city);

        $service->update($city, $request->validated());

        return redirect()->route('admin.cities.index')
            ->with('status', __('location.city_updated'));
    }

    public function deactivate(City $city, ManageCityService $service): RedirectResponse
    {
        $this->authorize('deactivate', $city);

        try {
            $service->deactivate($city);
        } catch (DomainException $exception) {
            return back()->withErrors(['city' => $exception->getMessage()]);
        }

        return back()->with('status', __('location.city_deactivated'));
    }

    public function activate(City $city, ManageCityService $service): RedirectResponse
    {
        $this->authorize('update', $city);

        try {
            $service->activate($city);
        } catch (DomainException $exception) {
            return back()->withErrors(['city' => $exception->getMessage()]);
        }

        return back()->with('status', __('location.city_activated'));
    }
}
