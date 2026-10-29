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
            'accuracy_m' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100000'],
            'discovery_mode' => ['sometimes', 'nullable', 'string', Rule::in(['manual', 'device', 'nationwide'])],
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
     *     accuracy_m?: float,
     *     discovery_mode?: string,
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

        $mode = $this->filled('discovery_mode')
            ? $this->string('discovery_mode')->toString()
            : null;

        if ($mode !== null) {
            $filters['discovery_mode'] = $mode;
        }

        // Exclusive params by mode — never mix stale city_id with device coords.
        if ($mode === 'device') {
            if ($this->filled('latitude') && $this->filled('longitude')) {
                $filters['latitude'] = (float) $this->input('latitude');
                $filters['longitude'] = (float) $this->input('longitude');
            }
            if ($this->filled('accuracy_m')) {
                $filters['accuracy_m'] = (float) $this->input('accuracy_m');
            }
        } elseif ($mode === 'nationwide') {
            $filters['city_id'] = null;
        } elseif ($mode === 'manual') {
            // Place selection may send lat/lng without city_id (beyond cities table).
            if ($this->filled('latitude') && $this->filled('longitude')) {
                $filters['latitude'] = (float) $this->input('latitude');
                $filters['longitude'] = (float) $this->input('longitude');
            }
            if (array_key_exists('city_id', $this->query()) || $this->exists('city_id')) {
                $filters['city_id'] = $this->filled('city_id') ? $this->integer('city_id') : null;
            }
        } else {
            // Legacy: keep city_id key semantics; coords only when city_id omitted.
            if (array_key_exists('city_id', $this->query())) {
                $filters['city_id'] = $this->filled('city_id') ? $this->integer('city_id') : null;
            } elseif ($this->exists('city_id')) {
                $filters['city_id'] = $this->filled('city_id') ? $this->integer('city_id') : null;
            }

            if (! array_key_exists('city_id', $filters)
                && $this->filled('latitude')
                && $this->filled('longitude')) {
                $filters['latitude'] = (float) $this->input('latitude');
                $filters['longitude'] = (float) $this->input('longitude');
            }
        }

        if ($this->filled('distance_km')) {
            $filters['distance_km'] = $this->integer('distance_km');
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
