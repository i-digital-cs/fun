<?php

namespace App\Models;

use App\Enums\ReportableType;
use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'reporter_id',
        'reportable_type',
        'reportable_id',
        'reason',
        'description',
        'status',
        'moderation_action',
        'reviewed_by',
        'reviewed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reason' => ReportReason::class,
            'status' => ReportStatus::class,
            'reportable_type' => ReportableType::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function resolveReportable(): Event|EventMessage|User|null
    {
        $type = $this->reportable_type instanceof ReportableType
            ? $this->reportable_type
            : ReportableType::tryFrom((string) $this->reportable_type);

        if ($type === null) {
            return null;
        }

        return $type->modelClass()::query()->find($this->reportable_id);
    }
}
