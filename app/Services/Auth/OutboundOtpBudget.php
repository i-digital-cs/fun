<?php

namespace App\Services\Auth;

use App\Enums\OutboundOtpLane;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Soft circuit-breaker for OTP outbound volume.
 *
 * Lanes prevent a registration flood from denying authenticated resends.
 * Windows roll by hour/day (never a permanent global deny).
 * Counters are incremented only after a successful SMTP accept (no double-count
 * on delivery failure; concurrent overshoot of ~worker count is acceptable).
 */
class OutboundOtpBudget
{
    public function allow(OutboundOtpLane $lane = OutboundOtpLane::AbuseProne): bool
    {
        [$hourLimit, $dayLimit] = $this->limitsFor($lane);

        $hourKey = $this->hourKey($lane);
        $dayKey = $this->dayKey($lane);

        $hourCount = (int) Cache::get($hourKey, 0);
        $dayCount = (int) Cache::get($dayKey, 0);

        if ($hourCount >= $hourLimit || $dayCount >= $dayLimit) {
            Log::warning('otp_outbound_budget_exceeded', [
                'lane' => $lane->value,
                'hour_count' => $hourCount,
                'day_count' => $dayCount,
                'hour_limit' => $hourLimit,
                'day_limit' => $dayLimit,
            ]);

            return false;
        }

        return true;
    }

    /**
     * Record a successful outbound OTP send against the lane counters.
     * Uses Cache::increment (atomic on database/redis drivers).
     */
    public function record(OutboundOtpLane $lane = OutboundOtpLane::AbuseProne): void
    {
        $hourKey = $this->hourKey($lane);
        $dayKey = $this->dayKey($lane);

        $this->ensureCounter($hourKey, now()->endOfHour());
        $this->ensureCounter($dayKey, now()->endOfDay());

        Cache::increment($hourKey);
        Cache::increment($dayKey);
    }

    /**
     * @return array{0: int, 1: int} hour limit, day limit
     */
    private function limitsFor(OutboundOtpLane $lane): array
    {
        if ($lane === OutboundOtpLane::Member) {
            $hour = max(1, (int) config('otp.outbound_member_per_hour', 80));
            $day = max($hour, (int) config('otp.outbound_member_per_day', 400));

            return [$hour, $day];
        }

        // Abuse-prone lane (legacy keys outbound_per_hour / outbound_per_day).
        $hour = max(1, (int) config('otp.outbound_per_hour', 120));
        $day = max($hour, (int) config('otp.outbound_per_day', 800));

        return [$hour, $day];
    }

    private function ensureCounter(string $key, \DateTimeInterface $expiresAt): void
    {
        // add() is atomic: only the first writer sets TTL.
        Cache::add($key, 0, $expiresAt);
    }

    private function hourKey(OutboundOtpLane $lane): string
    {
        return 'otp:outbound:'.$lane->value.':hour:'.now()->format('YmdH');
    }

    private function dayKey(OutboundOtpLane $lane): string
    {
        return 'otp:outbound:'.$lane->value.':day:'.now()->format('Ymd');
    }
}
