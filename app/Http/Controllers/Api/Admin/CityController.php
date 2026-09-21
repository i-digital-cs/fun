<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCityRequest;
use App\Http\Requests\Admin\UpdateCityRequest;
use App\Models\City;
use App\Services\Location\ManageCityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', City::class);

        $cities = City::query()->orderBy('name_en')->paginate(50);

        return response()->json($cities);
    }

    public function store(StoreCityRequest $request, ManageCityService $service): JsonResponse
    {
        $this->authorize('create', City::class);

        $city = $service->create($request->validated());

        return response()->json(['city' => $city], 201);
    }

    public function update(UpdateCityRequest $request, City $city, ManageCityService $service): JsonResponse
    {
        $this->authorize('update', $city);

        $city = $service->update($city, $request->validated());

        return response()->json(['city' => $city]);
    }

    public function deactivate(City $city, ManageCityService $service): JsonResponse
    {
        $this->authorize('deactivate', $city);

        try {
            $city = $service->deactivate($city);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['city' => $city, 'message' => __('location.city_deactivated')]);
    }

    public function activate(City $city, ManageCityService $service): JsonResponse
    {
        $this->authorize('update', $city);

        try {
            $city = $service->activate($city);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['city' => $city, 'message' => __('location.city_activated')]);
    }
}
