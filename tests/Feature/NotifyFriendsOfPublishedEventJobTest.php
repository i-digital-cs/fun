<?php

namespace Tests\Feature;

use App\Enums\EventStatus;
use App\Enums\FriendshipStatus;
use App\Jobs\NotifyFriendsOfPublishedEvent;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\Friendship;
use App\Models\User;
use App\Notifications\FriendEventNotification;
use App\Services\Friends\FriendshipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotifyFriendsOfPublishedEventJobTest extends TestCase
{
    use RefreshDatabase;

    private function acceptFriendship(User $publisher, User $friend): void
    {
        Friendship::query()->create([
            'requester_id' => $publisher->id,
            'addressee_id' => $friend->id,
            'status' => FriendshipStatus::Accepted,
        ]);
    }

    public function test_skips_ended_published_events(): void
    {
        Notification::fake();

        $publisher = User::factory()->active()->create(['notify_friend_events' => true]);
        $friend = User::factory()->active()->create(['notify_friend_events' => true]);
        $this->acceptFriendship($publisher, $friend);

        $event = Event::factory()->published()->create([
            'created_by' => $publisher->id,
            'category_id' => Category::factory()->create()->id,
            'city_id' => City::factory()->create()->id,
            'status' => EventStatus::Published,
            'starts_at' => now()->subDays(3),
            'ends_at' => now()->subDay(),
        ]);

        (new NotifyFriendsOfPublishedEvent($event->id, $publisher->id))->handle(
            app(FriendshipService::class),
            app(\App\Services\Safety\UserBlockService::class),
        );

        Notification::assertNothingSent();
    }

    public function test_skips_duplicate_database_notification_for_same_event(): void
    {
        $publisher = User::factory()->active()->create(['notify_friend_events' => true]);
        $friend = User::factory()->active()->create(['notify_friend_events' => true]);
        $this->acceptFriendship($publisher, $friend);

        $event = Event::factory()->published()->create([
            'created_by' => $publisher->id,
            'category_id' => Category::factory()->create()->id,
            'city_id' => City::factory()->create()->id,
            'status' => EventStatus::Published,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDays(2),
        ]);

        $friend->notify(new FriendEventNotification($publisher, $event));
        $this->assertSame(1, $friend->notifications()->count());

        (new NotifyFriendsOfPublishedEvent($event->id, $publisher->id))->handle(
            app(FriendshipService::class),
            app(\App\Services\Safety\UserBlockService::class),
        );

        $this->assertSame(1, $friend->fresh()->notifications()->count());
    }

    public function test_skips_unpublished_events(): void
    {
        Notification::fake();

        $publisher = User::factory()->active()->create();
        $event = Event::factory()->create([
            'created_by' => $publisher->id,
            'category_id' => Category::factory()->create()->id,
            'city_id' => City::factory()->create()->id,
            'status' => EventStatus::Draft,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDays(2),
        ]);

        (new NotifyFriendsOfPublishedEvent($event->id, $publisher->id))->handle(
            app(FriendshipService::class),
            app(\App\Services\Safety\UserBlockService::class),
        );

        Notification::assertNothingSent();
    }
}
