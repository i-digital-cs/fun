<?php

namespace App\Http\Requests\Events;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canParticipate() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'city_id' => ['required', 'integer', Rule::exists('cities', 'id')->where('is_active', true)],
            'venue' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'maps_url' => ['nullable', 'string', 'max:500'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'max_participants' => ['required', 'integer', 'min:1', 'max:500'],
            'requirements' => ['nullable', 'string', 'max:2000'],
            'expected_cost' => ['nullable', 'string', 'max:255'],
            'cost_per_person' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
