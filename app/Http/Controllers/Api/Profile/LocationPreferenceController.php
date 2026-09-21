<?php

namespace App\Http\Controllers\Api\Profile;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateLocationPreferenceRequest;
use App\Services\Location\UpdateUserLocationPreferenceService;
use Illuminate\Http\JsonResponse;

class LocationPreferenceController extends Controller
{
    public function update(
        UpdateLocationPreferenceRequest $request,
        UpdateUserLocationPreferenceService $service,
    ): JsonResponse {
        try {
            $user = $service->update($request->user(), $request->validated());
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('location.preferences_saved'),
            'user' => [
                'id' => $user->id,
                'city_id' => $user->city_id,
                'distance_km' => $user->distance_km,
                'city' => $user->city ? [
                    'id' => $user->city->id,
                    'name' => $user->city->localizedName(),
                    'name_en' => $user->city->name_en,
                    'name_ar' => $user->city->name_ar,
                ] : null,
            ],
        ]);
    }
}
