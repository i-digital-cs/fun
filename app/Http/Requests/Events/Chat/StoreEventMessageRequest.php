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
            'body' => ['required', 'string', 'max:'.PostEventMessageService::MAX_LENGTH],
            'client_uuid' => ['sometimes', 'nullable', 'string', 'max:36'],
            'reply_to_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ];
    }
}
