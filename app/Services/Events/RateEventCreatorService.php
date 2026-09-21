<?php

namespace App\Services\Events;

use App\Enums\EventStatus;
use App\Exceptions\DomainException;
use App\Models\Event;
use App\Models\EventRating;
use App\Models\User;
use App\Services\Safety\UserBlockService;
use App\Support\ChatMessageSanitizer;

class RateEventCreatorService
{
    public const MIN_STARS = 1;

    public const MAX_STARS = 5;

    public const MAX_COMMENT = 1000;

    public function __construct(private readonly UserBlockService $blocks)
    {
    }

    public function rate(User $rater, Event $event, int $stars, ?string $comment = null): EventRating
    {
        if (! $rater->canParticipate()) {
            throw new DomainException(__('ratings.not_allowed'));
        }

        if (! $event->isReadyForRatings()) {
            throw new DomainException(__('ratings.not_ready'));
        }

        if (! $event->isMember($rater)) {
            throw new DomainException(__('ratings.members_only'));
        }

        $creator = $event->creator()->first();

        if ($creator === null) {
            throw new DomainException(__('ratings.creator_missing'));
        }

        if ($creator->id === $rater->id) {
            throw new DomainException(__('ratings.cannot_rate_self'));
        }

        if ($this->blocks->isBlockedEitherWay($rater, $creator)) {
            throw new DomainException(__('ratings.blocked'));
        }

        if ($stars < self::MIN_STARS || $stars > self::MAX_STARS) {
            throw new DomainException(__('ratings.invalid_stars'));
        }

        $cleanComment = null;

        if ($comment !== null) {
            $cleanComment = ChatMessageSanitizer::clean($comment);

            if ($cleanComment === '') {
                $cleanComment = null;
            }

            if ($cleanComment !== null && mb_strlen($cleanComment) > self::MAX_COMMENT) {
                throw new DomainException(__('ratings.comment_too_long'));
            }
        }

        $existing = EventRating::query()
            ->where('event_id', $event->id)
            ->where('rater_id', $rater->id)
            ->first();

        if ($existing) {
            $existing->forceFill([
                'rated_user_id' => $creator->id,
                'stars' => $stars,
                'comment' => $cleanComment,
            ])->save();

            return $existing->refresh()->load(['rater:id,name', 'ratedUser:id,name']);
        }

        return EventRating::query()->create([
            'event_id' => $event->id,
            'rater_id' => $rater->id,
            'rated_user_id' => $creator->id,
            'stars' => $stars,
            'comment' => $cleanComment,
        ])->load(['rater:id,name', 'ratedUser:id,name']);
    }
}
