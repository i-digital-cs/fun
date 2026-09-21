<?php

namespace App\Http\Requests\Safety;

use App\Enums\ReportStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewReportRequest extends FormRequest
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
            'status' => [
                'required',
                'string',
                Rule::in([
                    ReportStatus::Reviewed->value,
                    ReportStatus::Resolved->value,
                    ReportStatus::Dismissed->value,
                ]),
            ],
            'moderation_action' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
