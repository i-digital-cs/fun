<?php

namespace App\Services\Marketing;

use App\Models\Marketing\MarketingAuditLog;
use App\Models\User;
use App\Repositories\Marketing\MarketingAuditLogRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MarketingAuditService
{
    public function __construct(
        private readonly MarketingAuditLogRepository $logs,
    ) {}

    /**
     * @param  array<string, mixed>|null  $meta  Must already be redacted (no tokens/secrets).
     */
    public function log(
        ?User $actor,
        string $action,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?array $meta = null,
        ?Request $request = null,
    ): MarketingAuditLog {
        return $this->logs->create([
            'actor_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'ip' => $request?->ip(),
            'correlation_id' => $this->correlationId($request),
            'meta_json' => $this->redactMeta($meta),
            'created_at' => now(),
        ]);
    }

    private function correlationId(?Request $request): string
    {
        if ($request === null) {
            return (string) Str::uuid();
        }

        $existing = $request->headers->get('X-Request-Id')
            ?? $request->headers->get('X-Correlation-Id');

        return is_string($existing) && $existing !== ''
            ? substr($existing, 0, 64)
            : (string) Str::uuid();
    }

    /**
     * @param  array<string, mixed>|null  $meta
     * @return array<string, mixed>|null
     */
    private function redactMeta(?array $meta): ?array
    {
        if ($meta === null) {
            return null;
        }

        $blocked = [
            'access_token',
            'refresh_token',
            'token',
            'secret',
            'client_secret',
            'app_secret',
            'encrypted_payload',
            'password',
            'authorization',
        ];

        $walk = function ($value) use (&$walk, $blocked) {
            if (! is_array($value)) {
                return $value;
            }

            $out = [];
            foreach ($value as $key => $item) {
                if (is_string($key) && in_array(strtolower($key), $blocked, true)) {
                    $out[$key] = '[redacted]';
                    continue;
                }
                $out[$key] = $walk($item);
            }

            return $out;
        };

        return $walk($meta);
    }
}
