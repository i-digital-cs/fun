<?php

namespace App\Http\Requests\Events;

use App\Services\Events\RateEventCreatorService;
use Illuminate\Foundation\Http\FormRequest;

class StoreEventRatingRequest extends FormRequest
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
            'stars' => [
                'required',
                'integer',
                'min:'.RateEventCreatorService::MIN_STARS,
                'max:'.RateEventCreatorService::MAX_STARS,
            ],
            'comment' => ['nullable', 'string', 'max:'.RateEventCreatorService::MAX_COMMENT],
        ];
    }
}
