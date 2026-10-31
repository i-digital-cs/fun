<?php

namespace App\Support\Reporting;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Inclusive calendar-day reporting window in app timezone.
 * Comparisons use the immediately preceding window of equal length.
 */
final class ReportingPeriod
{
    public const PRESET_TODAY = 'today';

    public const PRESET_7D = '7d';

    public const PRESET_30D = '30d';

    public const PRESET_90D = '90d';

    public const PRESET_CUSTOM = 'custom';

    /** @var list<string> */
    public const PRESETS = [
        self::PRESET_TODAY,
        self::PRESET_7D,
        self::PRESET_30D,
        self::PRESET_90D,
        self::PRESET_CUSTOM,
    ];

    private const MAX_CUSTOM_DAYS = 90;

    public function __construct(
        public readonly string $preset,
        public readonly Carbon $start,
        public readonly Carbon $endExclusive,
        public readonly Carbon $previousStart,
        public readonly Carbon $previousEndExclusive,
        public readonly ?string $customFrom = null,
        public readonly ?string $customTo = null,
    ) {}

    public static function fromRequest(Request $request, ?CarbonInterface $now = null): self
    {
        $preset = (string) $request->input('range', self::PRESET_7D);
        if (! in_array($preset, self::PRESETS, true)) {
            $preset = self::PRESET_7D;
        }

        if ($preset === self::PRESET_CUSTOM) {
            return self::custom(
                (string) $request->input('from', ''),
                (string) $request->input('to', ''),
                $now,
            );
        }

        return self::preset($preset, $now);
    }

    public static function preset(string $preset, ?CarbonInterface $now = null): self
    {
        if (! in_array($preset, self::PRESETS, true) || $preset === self::PRESET_CUSTOM) {
            throw new InvalidArgumentException("Unsupported reporting preset [{$preset}].");
        }

        $tz = (string) config('app.timezone');
        $now = ($now ?? now($tz))->copy()->timezone($tz);
        $startOfToday = $now->copy()->startOfDay();
        $endExclusive = $startOfToday->copy()->addDay();

        [$start, $days] = match ($preset) {
            self::PRESET_TODAY => [$startOfToday->copy(), 1],
            self::PRESET_7D => [$startOfToday->copy()->subDays(6), 7],
            self::PRESET_30D => [$startOfToday->copy()->subDays(29), 30],
            self::PRESET_90D => [$startOfToday->copy()->subDays(89), 90],
        };

        $previousEnd = $start->copy();
        $previousStart = $previousEnd->copy()->subDays($days);

        return new self(
            preset: $preset,
            start: $start,
            endExclusive: $endExclusive,
            previousStart: $previousStart,
            previousEndExclusive: $previousEnd,
        );
    }

    public static function custom(string $from, string $to, ?CarbonInterface $now = null): self
    {
        $tz = (string) config('app.timezone');
        $now = ($now ?? now($tz))->copy()->timezone($tz);
        $today = $now->copy()->startOfDay();

        try {
            $start = Carbon::parse($from, $tz)->startOfDay();
            $endDay = Carbon::parse($to, $tz)->startOfDay();
        } catch (\Throwable) {
            return self::preset(self::PRESET_7D, $now);
        }

        if ($endDay->lt($start)) {
            [$start, $endDay] = [$endDay, $start];
        }

        if ($endDay->gt($today)) {
            $endDay = $today->copy();
        }

        $days = $start->diffInDays($endDay) + 1;
        if ($days > self::MAX_CUSTOM_DAYS) {
            $start = $endDay->copy()->subDays(self::MAX_CUSTOM_DAYS - 1);
            $days = self::MAX_CUSTOM_DAYS;
        }

        $endExclusive = $endDay->copy()->addDay();
        $previousEnd = $start->copy();
        $previousStart = $previousEnd->copy()->subDays($days);

        return new self(
            preset: self::PRESET_CUSTOM,
            start: $start,
            endExclusive: $endExclusive,
            previousStart: $previousStart,
            previousEndExclusive: $previousEnd,
            customFrom: $start->toDateString(),
            customTo: $endDay->toDateString(),
        );
    }

    public function dayCount(): int
    {
        return (int) $this->start->diffInDays($this->endExclusive);
    }

    public function label(): string
    {
        return match ($this->preset) {
            self::PRESET_TODAY => __('admin.range_today'),
            self::PRESET_7D => __('admin.range_7d'),
            self::PRESET_30D => __('admin.range_30d'),
            self::PRESET_90D => __('admin.range_90d'),
            self::PRESET_CUSTOM => __('admin.range_custom_label', [
                'from' => $this->customFrom,
                'to' => $this->customTo,
            ]),
            default => __('admin.range_7d'),
        };
    }

    public function cacheKey(): string
    {
        return implode(':', [
            $this->preset,
            $this->start->toDateString(),
            $this->endExclusive->toDateString(),
        ]);
    }

    /**
     * @return array{preset: string, from: string, to: string, label: string, days: int, timezone: string}
     */
    public function meta(): array
    {
        return [
            'preset' => $this->preset,
            'from' => $this->start->toDateString(),
            'to' => $this->endExclusive->copy()->subDay()->toDateString(),
            'label' => $this->label(),
            'days' => $this->dayCount(),
            'timezone' => (string) config('app.timezone'),
        ];
    }
}
