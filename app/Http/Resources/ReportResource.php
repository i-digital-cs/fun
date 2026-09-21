<?php

namespace App\Http\Resources;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Report */
class ReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reportable_type' => $this->reportable_type->value,
            'reportable_id' => $this->reportable_id,
            'reason' => $this->reason->value,
            'description' => $this->description,
            'status' => $this->status->value,
            'moderation_action' => $this->when(
                $request->user()?->isPlatformAdmin(),
                $this->moderation_action
            ),
            'reporter' => $this->whenLoaded('reporter', fn () => [
                'id' => $this->reporter->id,
                'name' => $this->reporter->name,
            ]),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
