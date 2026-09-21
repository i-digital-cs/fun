<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            ['name_en' => 'Obour', 'name_ar' => 'العبور', 'latitude' => 30.2285, 'longitude' => 31.4769],
            ['name_en' => 'Cairo', 'name_ar' => 'القاهرة', 'latitude' => 30.0444, 'longitude' => 31.2357],
            ['name_en' => 'New Cairo', 'name_ar' => 'القاهرة الجديدة', 'latitude' => 30.0300, 'longitude' => 31.4700],
            ['name_en' => 'Giza', 'name_ar' => 'الجيزة', 'latitude' => 30.0131, 'longitude' => 31.2089],
            ['name_en' => 'Alexandria', 'name_ar' => 'الإسكندرية', 'latitude' => 31.2001, 'longitude' => 29.9187],
            ['name_en' => '6th of October', 'name_ar' => 'السادس من أكتوبر', 'latitude' => 29.9285, 'longitude' => 30.9188],
            ['name_en' => 'Sheikh Zayed', 'name_ar' => 'الشيخ زايد', 'latitude' => 30.0214, 'longitude' => 30.9746],
            ['name_en' => 'Mansoura', 'name_ar' => 'المنصورة', 'latitude' => 31.0409, 'longitude' => 31.3785],
            ['name_en' => 'Tanta', 'name_ar' => 'طنطا', 'latitude' => 30.7865, 'longitude' => 31.0004],
            ['name_en' => 'Ismailia', 'name_ar' => 'الإسماعيلية', 'latitude' => 30.5965, 'longitude' => 32.2715],
            ['name_en' => 'Port Said', 'name_ar' => 'بورسعيد', 'latitude' => 31.2653, 'longitude' => 32.3019],
            ['name_en' => 'Suez', 'name_ar' => 'السويس', 'latitude' => 29.9668, 'longitude' => 32.5498],
            ['name_en' => 'Zagazig', 'name_ar' => 'الزقازيق', 'latitude' => 30.5877, 'longitude' => 31.5020],
            ['name_en' => 'Damietta', 'name_ar' => 'دمياط', 'latitude' => 31.4165, 'longitude' => 31.8133],
            ['name_en' => 'Hurghada', 'name_ar' => 'الغردقة', 'latitude' => 27.2579, 'longitude' => 33.8116],
        ];

        foreach ($cities as $city) {
            City::query()->updateOrCreate(
                ['slug' => Str::slug($city['name_en'])],
                [
                    'name_en' => $city['name_en'],
                    'name_ar' => $city['name_ar'],
                    'latitude' => $city['latitude'],
                    'longitude' => $city['longitude'],
                    'is_active' => true,
                ]
            );
        }
    }
}
