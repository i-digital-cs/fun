<?php

namespace Database\Factories;

use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<City>
 */
class CityFactory extends Factory
{
    protected $model = City::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nameEn = fake()->unique()->city();

        return [
            'name_en' => $nameEn,
            'name_ar' => $nameEn,
            'slug' => Str::slug($nameEn).'-'.fake()->unique()->numerify('###'),
            'latitude' => fake()->latitude(22, 31),
            'longitude' => fake()->longitude(25, 35),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
