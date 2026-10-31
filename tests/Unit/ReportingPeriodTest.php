<?php

namespace Tests\Unit;

use App\Support\Reporting\ReportingPeriod;
use Carbon\Carbon;
use Tests\TestCase;

class ReportingPeriodTest extends TestCase
{
    public function test_today_previous_period_is_yesterday(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-31 15:00:00', 'UTC'));
        config(['app.timezone' => 'UTC']);

        $period = ReportingPeriod::preset(ReportingPeriod::PRESET_TODAY, now('UTC'));

        $this->assertSame('2026-10-31', $period->start->toDateString());
        $this->assertSame('2026-11-01', $period->endExclusive->toDateString());
        $this->assertSame('2026-10-30', $period->previousStart->toDateString());
        $this->assertSame('2026-10-31', $period->previousEndExclusive->toDateString());
        $this->assertSame(1, $period->dayCount());

        Carbon::setTestNow();
    }

    public function test_seven_day_window_and_custom_clamp(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-31 12:00:00', 'UTC'));
        config(['app.timezone' => 'UTC']);

        $week = ReportingPeriod::preset(ReportingPeriod::PRESET_7D, now('UTC'));
        $this->assertSame(7, $week->dayCount());
        $this->assertSame('2026-10-25', $week->start->toDateString());

        $custom = ReportingPeriod::custom('2026-01-01', '2026-10-31', now('UTC'));
        $this->assertSame(ReportingPeriod::PRESET_CUSTOM, $custom->preset);
        $this->assertSame(90, $custom->dayCount());
        $this->assertSame('2026-08-03', $custom->start->toDateString());

        Carbon::setTestNow();
    }
}
