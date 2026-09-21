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
            'q' => ['sometimes', 'nullable', 'string', 'max:100'],
            'per_page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:50'],
        ];
    }

    /**
     * @return array{
     *     category_id?: int,
     *     city_id?: int|null,
     *     distance_km?: int,
     *     q?: string,
     *     per_page?: int
     * }
     */
    public function filters(): array
    {
        $filters = [];

        if ($this->filled('category_id')) {
            $filters['category_id'] = $this->integer('category_id');
        }

        if ($this->has('city_id')) {
            $filters['city_id'] = $this->filled('city_id') ? $this->integer('city_id') : null;
        }

        if ($this->filled('distance_km')) {
            $filters['distance_km'] = $this->integer('distance_km');
        }

        if ($this->filled('q')) {
            $filters['q'] = $this->string('q')->trim()->toString();
        }

        if ($this->filled('per_page')) {
            $filters['per_page'] = $this->integer('per_page');
        }

        return $filters;
    }
}
