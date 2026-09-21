<?php

namespace App\Http\Requests\Events;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['sometimes', 'required', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'city_id' => ['sometimes', 'required', 'integer', Rule::exists('cities', 'id')->where('is_active', true)],
            'venue' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['sometimes', 'required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'max_participants' => ['sometimes', 'required', 'integer', 'min:1', 'max:500'],
            'requirements' => ['nullable', 'string', 'max:2000'],
            'expected_cost' => ['nullable', 'string', 'max:255'],
            'cost_per_person' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
