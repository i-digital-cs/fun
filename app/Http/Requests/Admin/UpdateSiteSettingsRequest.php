<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $version = ['sometimes', 'required', 'string', 'max:32', 'regex:/^\d+(\.\d+){0,3}$/'];

        return [
            'display_name' => ['required', 'string', 'max:80'],
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'remove_logo' => ['sometimes', 'boolean'],
            'android_latest_version' => $version,
            'android_min_version' => $version,
            'ios_latest_version' => $version,
            'ios_min_version' => $version,
            'force_update' => ['sometimes', 'boolean'],
            'android_store_url' => ['nullable', 'url', 'max:500'],
            'ios_store_url' => ['nullable', 'url', 'max:500'],
            'update_message_en' => ['nullable', 'string', 'max:500'],
            'update_message_ar' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('remove_logo')) {
            $this->merge([
                'remove_logo' => filter_var($this->input('remove_logo'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        if ($this->has('force_update')) {
            $this->merge([
                'force_update' => filter_var($this->input('force_update'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}
