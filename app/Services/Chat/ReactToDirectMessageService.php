<?php

namespace App\Services\Chat;

use App\Exceptions\DomainException;
use App\Models\DirectConversation;
use App\Models\DirectMessage;
use App\Models\DirectMessageReaction;
use App\Models\User;
use App\Services\Friends\DirectMessageService;
use Illuminate\Support\Collection;

class ReactToDirectMessageService
{
    public function __construct(private readonly DirectMessageService $dms) {}

    /**
     * Toggle emoji reaction. Returns aggregate counts + caller's emojis.
     *
     * @return array{emoji_counts: array<string, int>, my_emojis: list<string>}
     */
    public function toggle(User $user, DirectConversation $conversation, DirectMessage $message, string $emoji): array
    {
        if (! ChatV2Capabilities::directMessageReactions()) {
            throw new DomainException(__('chat.reactions_unavailable'));
        }

        $this->dms->assertParticipant($user, $conversation);

        if ((int) $message->conversation_id !== (int) $conversation->id) {
            throw new DomainException(__('chat.message_not_found'));
        }

        $emoji = trim($emoji);
        if (! in_array($emoji, DirectMessageReaction::ALLOWED, true)) {
            throw new DomainException(__('chat.invalid_reaction'));
        }

        $existing = DirectMessageReaction::query()
            ->where('message_id', $message->id)
            ->where('user_id', $user->id)
            ->where('emoji', $emoji)
            ->first();

        if ($existing !== null) {
            $existing->delete();
        } else {
            DirectMessageReaction::query()->create([
                'message_id' => $message->id,
                'user_id' => $user->id,
                'emoji' => $emoji,
            ]);
        }

        return $this->summary($message, $user);
    }

    /**
     * @return array{emoji_counts: array<string, int>, my_emojis: list<string>}
     */
    public function summary(DirectMessage $message, ?User $viewer = null): array
    {
        /** @var Collection<int, object{emoji: string, c: int}> $rows */
        $rows = DirectMessageReaction::query()
            ->selectRaw('emoji, COUNT(*) as c')
            ->where('message_id', $message->id)
            ->groupBy('emoji')
            ->get();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(string) $row->emoji] = (int) $row->c;
        }

        $mine = [];
        if ($viewer !== null) {
            $mine = DirectMessageReaction::query()
                ->where('message_id', $message->id)
                ->where('user_id', $viewer->id)
                ->pluck('emoji')
                ->map(fn ($e) => (string) $e)
                ->values()
                ->all();
        }

        return [
            'emoji_counts' => $counts,
            'my_emojis' => $mine,
        ];
    }
}
