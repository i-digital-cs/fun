<?php

namespace App\Services\Location;

use App\Exceptions\DomainException;
use App\Models\City;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateUserLocationPreferenceService
{
    /**
     * @param  array{city_id: int, distance_km: int}  $data
     */
    public function update(User $user, array $data): User
    {
        $allowedDistances = config('location.distances_km');

        if (! in_array((int) $data['distance_km'], $allowedDistances, true)) {
            throw ValidationException::withMessages([
                'distance_km' => [__('location.invalid_distance')],
            ]);
        }

        $city = City::query()->active()->find($data['city_id']);

        if ($city === null) {
            throw new DomainException(__('location.city_unavailable'));
        }

        return DB::transaction(function () use ($user, $city, $data) {
            $user->forceFill([
                'city_id' => $city->id,
                'distance_km' => (int) $data['distance_km'],
            ])->save();

            return $user->refresh()->load('city');
        });
    }
}
