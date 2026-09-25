<?php

namespace Tests\Feature;

use App\Enums\EventMemberRole;
use App\Enums\EventStatus;
use App\Enums\JoinRequestStatus;
use App\Mail\JoinRequestReceivedMail;
use App\Models\Category;
use App\Models\City;
use App\Models\Event;
use App\Models\EventJoinRequest;
use App\Models\EventMember;
use App\Models\User;
use App\Services\Events\ReviewJoinRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EventMembershipTest extends TestCase
{
    use RefreshDatabase;

    private function publishedEvent(User $owner, int $maxParticipants = 2): Event
    {
        $category = Category::factory()->create();
        $city = City::factory()->create();

        return Event::factory()->published()->create([
            'created_by' => $owner->id,
            'category_id' => $category->id,
            'city_id' => $city->id,
            'max_participants' => $maxParticipants,
            'status' => EventStatus::Published,
        ]);
    }

    public function test_user_can_request_join_and_owner_can_accept(): void
    {
        Mail::fake();

        $owner = User::factory()->active()->create();
        $applicant = User::factory()->active()->create();
        $event = $this->publishedEvent($owner, 5);

        Sanctum::actingAs($applicant);
        $this->postJson("/api/events/{$event->id}/join")
            ->assertCreated()
            ->assertJsonPath('join_request.status', 'pending');

        Mail::assertSent(JoinRequestReceivedMail::class, function (JoinRequestReceivedMail $mail) use ($owner, $applicant, $event) {
            return $mail->hasTo($owner->email)
                && (int) $mail->requester->id === (int) $applicant->id
                && (int) $mail->event->id === (int) $event->id;
        });

        $joinRequest = EventJoinRequest::query()->firstOrFail();

        Sanctum::actingAs($owner);
        $this->postJson("/api/events/{$event->id}/requests/{$joinRequest->id}/accept")
            ->assertOk()
            ->assertJsonPath('member.role', 'member');

        $this->assertDatabaseHas('event_members', [
            'event_id' => $event->id,
            'user_id' => $applicant->id,
            'role' => EventMemberRole::Member->value,
        ]);
    }

    public function test_accepting_join_requests_respects_capacity_transactionally(): void
    {
        $owner = User::factory()->active()->create();
        $first = User::factory()->active()->create();
        $second = User::factory()->active()->create();
        $event = $this->publishedEvent($owner, 2); // owner + 1 slot

        $req1 = EventJoinRequest::query()->create([
            'event_id' => $event->id,
            'user_id' => $first->id,
            'status' => JoinRequestStatus::Pending,
        ]);
        $req2 = EventJoinRequest::query()->create([
            'event_id' => $event->id,
            'user_id' => $second->id,
            'status' => JoinRequestStatus::Pending,
        ]);

        $service = app(ReviewJoinRequestService::class);
        $service->accept($owner, $req1);

        $this->expectException(\App\Exceptions\DomainException::class);
        $service->accept($owner, $req2);
    }

    public function test_owner_can_promote_transfer_and_then_leave(): void
    {
        $owner = User::factory()->active()->create();
        $memberUser = User::factory()->active()->create();
        $event = $this->publishedEvent($owner, 5);

        EventMember::query()->create([
            'event_id' => $event->id,
            'user_id' => $memberUser->id,
            'role' => EventMemberRole::Member,
        ]);

        Sanctum::actingAs($owner);

        $this->postJson("/api/events/{$event->id}/members/{$memberUser->id}/promote")
            ->assertOk()
            ->assertJsonPath('member.role', 'admin');

        $this->postJson("/api/events/{$event->id}/transfer", [
            'user_id' => $memberUser->id,
        ])->assertOk();

        $this->assertDatabaseHas('event_members', [
            'event_id' => $event->id,
            'user_id' => $memberUser->id,
            'role' => EventMemberRole::Owner->value,
        ]);

        $this->postJson("/api/events/{$event->id}/leave")->assertOk();

        $this->assertDatabaseMissing('event_members', [
            'event_id' => $event->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_owner_cannot_leave_without_transfer(): void
    {
        $owner = User::factory()->active()->create();
        $event = $this->publishedEvent($owner, 5);

        Sanctum::actingAs($owner);

        $this->postJson("/api/events/{$event->id}/leave")
            ->assertStatus(422);
    }

    public function test_user_can_cancel_pending_join_request(): void
    {
        $owner = User::factory()->active()->create();
        $applicant = User::factory()->active()->create();
        $event = $this->publishedEvent($owner, 5);

        EventJoinRequest::query()->create([
            'event_id' => $event->id,
            'user_id' => $applicant->id,
            'status' => JoinRequestStatus::Pending,
        ]);

        Sanctum::actingAs($applicant);
        $this->postJson("/api/events/{$event->id}/join/cancel")
            ->assertOk()
            ->assertJsonPath('join_action', 'request_to_join');

        $this->assertDatabaseHas('event_join_requests', [
            'event_id' => $event->id,
            'user_id' => $applicant->id,
            'status' => JoinRequestStatus::Cancelled->value,
        ]);
    }

    public function test_non_manager_cannot_accept_join_request(): void
    {
        $owner = User::factory()->active()->create();
        $outsider = User::factory()->active()->create();
        $applicant = User::factory()->active()->create();
        $event = $this->publishedEvent($owner, 5);

        $joinRequest = EventJoinRequest::query()->create([
            'event_id' => $event->id,
            'user_id' => $applicant->id,
            'status' => JoinRequestStatus::Pending,
        ]);

        Sanctum::actingAs($outsider);
        $this->postJson("/api/events/{$event->id}/requests/{$joinRequest->id}/accept")
            ->assertForbidden();
    }
}
