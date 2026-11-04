<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserSex;
use App\Support\EmailNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => EmailNormalizer::normalize($this->input('email')),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:1', 'max:120'],
            'email' => ['required', 'string', 'email:filter', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'sex' => ['required', Rule::enum(UserSex::class)],
            'locale' => ['nullable', 'in:en,ar'],
            // Optional tokens when Turnstile is enabled for a channel; Flutter may omit.
            'cf-turnstile-response' => ['sometimes', 'nullable', 'string', 'max:4096'],
            'turnstile_token' => ['sometimes', 'nullable', 'string', 'max:4096'],
        ];
    }
}
