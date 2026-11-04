<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class SchedulerRegistrationTest extends TestCase
{
    public function test_account_purge_deletions_is_scheduled_daily(): void
    {
        /** @var Schedule $schedule */
        $schedule = $this->app->make(Schedule::class);

        $events = collect($schedule->events());
        $purge = $events->first(
            fn ($event) => str_contains($event->command ?? '', 'account:purge-deletions')
                || str_contains($event->description ?? '', 'account:purge-deletions'),
        );

        $this->assertNotNull($purge, 'account:purge-deletions should be registered on the schedule');
        $this->assertSame('0 0 * * *', $purge->expression);
    }
}
