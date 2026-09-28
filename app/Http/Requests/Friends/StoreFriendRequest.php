<?php

namespace App\Http\Requests\Friends;

use Illuminate\Foundation\Http\FormRequest;

class StoreFriendRequest extends FormRequest
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
            'public_id' => ['required', 'string', 'max:32'],
        ];
    }
}
