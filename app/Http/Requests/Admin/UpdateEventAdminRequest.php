<?php

namespace App\Http\Requests\Admin;

use App\Enums\EventStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPlatformAdmin() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'city_id' => ['required', 'integer', Rule::exists('cities', 'id')],
            'venue' => ['nullable', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'max_participants' => ['required', 'integer', 'min:1', 'max:500'],
            'requirements' => ['nullable', 'string', 'max:2000'],
            'expected_cost' => ['nullable', 'string', 'max:255'],
            'cost_per_person' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'status' => ['required', Rule::enum(EventStatus::class)],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
