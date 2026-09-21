<?php

namespace App\Http\Requests\Safety;

use App\Enums\ReportableType;
use App\Enums\ReportReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
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
            'reportable_type' => ['required', 'string', Rule::enum(ReportableType::class)],
            'reportable_id' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', Rule::enum(ReportReason::class)],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
