<?php

namespace App\Http\Requests\Events\Chat;

use App\Services\Events\Chat\PostEventMessageService;
use Illuminate\Foundation\Http\FormRequest;

class StoreEventMessageRequest extends FormRequest
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
            'body' => ['nullable', 'string', 'max:'.PostEventMessageService::MAX_LENGTH],
            'client_uuid' => ['sometimes', 'nullable', 'string', 'max:36'],
            'reply_to_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'media' => ['sometimes', 'nullable', 'file', 'max:10240'],
            'media_kind' => ['sometimes', 'nullable', 'string', 'in:voice,image,file'],
            'duration_ms' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:120000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $body = trim((string) $this->input('body', ''));
            if ($body === '' && ! $this->hasFile('media')) {
                $validator->errors()->add('body', __('chat.empty'));
            }
        });
    }
}
