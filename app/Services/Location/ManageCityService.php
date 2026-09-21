<?php

namespace App\Services\Location;

use App\Exceptions\DomainException;
use App\Models\City;
use App\Support\UniqueSlug;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ManageCityService
{
    /**
     * @param  array{name_en: string, name_ar: string, latitude: float|int|string, longitude: float|int|string, slug?: string|null, is_active?: bool}  $data
     */
    public function create(array $data): City
    {
        return DB::transaction(function () use ($data) {
            $city = City::query()->create([
                'name_en' => $data['name_en'],
                'name_ar' => $data['name_ar'],
                'slug' => UniqueSlug::for(City::class, $data['slug'] ?? $data['name_en'], null, 'city'),
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->bumpNearbyCitiesCache();

            return $city;
        });
    }

    /**
     * @param  array{name_en?: string, name_ar?: string, latitude?: float|int|string, longitude?: float|int|string, slug?: string|null, is_active?: bool}  $data
     */
    public function update(City $city, array $data): City
    {
        return DB::transaction(function () use ($city, $data) {
            if (array_key_exists('slug', $data) && filled($data['slug'])) {
                $data['slug'] = UniqueSlug::for(City::class, (string) $data['slug'], $city->id, 'city');
            }

            $city->fill(collect($data)->only([
                'name_en',
                'name_ar',
                'slug',
                'latitude',
                'longitude',
                'is_active',
            ])->all())->save();

            $this->bumpNearbyCitiesCache();

            return $city->refresh();
        });
    }

    public function deactivate(City $city): City
    {
        if (! $city->is_active) {
            throw new DomainException(__('location.city_already_inactive'));
        }

        $city->forceFill(['is_active' => false])->save();
        $this->bumpNearbyCitiesCache();

        return $city->refresh();
    }

    public function activate(City $city): City
    {
        if ($city->is_active) {
            throw new DomainException(__('location.city_already_active'));
        }

        $city->forceFill(['is_active' => true])->save();
        $this->bumpNearbyCitiesCache();

        return $city->refresh();
    }

    private function bumpNearbyCitiesCache(): void
    {
        Cache::forever('nearby_cities_version', ((int) Cache::get('nearby_cities_version', 0)) + 1);
    }
}
