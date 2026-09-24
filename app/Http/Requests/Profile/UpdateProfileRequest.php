<?php

namespace App\Http\Requests\Profile;

use App\Enums\UserSex;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
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
        $user = $this->user();

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'sex' => ['required', Rule::enum(UserSex::class)],
            'locale' => ['nullable', 'in:en,ar'],
            'current_password' => ['nullable', 'string'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('remove_photo')) {
            $this->merge([
                'remove_photo' => filter_var($this->input('remove_photo'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}
