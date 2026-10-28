<?php

namespace App\Http\Requests\Events;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DiscoverEventsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'city_id' => ['sometimes', 'nullable', 'integer', 'exists:cities,id'],
            'distance_km' => ['sometimes', 'nullable', 'integer', Rule::in(config('location.distances_km'))],
            // Ephemeral device GPS for discovery only — never persisted on the user.
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'q' => ['sometimes', 'nullable', 'string', 'max:100'],
            'when' => ['sometimes', 'nullable', 'string', Rule::in(['today', 'tomorrow', 'weekend'])],
            'per_page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    /**
     * @return array{
     *     category_id?: int,
     *     city_id?: int|null,
     *     distance_km?: int,
     *     latitude?: float,
     *     longitude?: float,
     *     q?: string,
     *     when?: string,
     *     per_page?: int
     * }
     */
    public function filters(): array
    {
        $filters = [];

        if ($this->filled('category_id')) {
            $filters['category_id'] = $this->integer('category_id');
        }

        // Explicit `city_id` key (even empty) = user chose All cities / nationwide.
        // Prefer the query bag so empty `city_id=` survives ConvertEmptyStringsToNull.
        if (array_key_exists('city_id', $this->query())) {
            $filters['city_id'] = $this->filled('city_id') ? $this->integer('city_id') : null;
        } elseif ($this->exists('city_id')) {
            $filters['city_id'] = $this->filled('city_id') ? $this->integer('city_id') : null;
        }

        if ($this->filled('distance_km')) {
            $filters['distance_km'] = $this->integer('distance_km');
        }

        if ($this->filled('latitude') && $this->filled('longitude')) {
            $filters['latitude'] = (float) $this->input('latitude');
            $filters['longitude'] = (float) $this->input('longitude');
        }

        if ($this->filled('q')) {
            $filters['q'] = $this->string('q')->trim()->toString();
        }

        if ($this->filled('when')) {
            $filters['when'] = $this->string('when')->toString();
        }

        if ($this->filled('per_page')) {
            $filters['per_page'] = $this->integer('per_page');
        }

        return $filters;
    }
}
