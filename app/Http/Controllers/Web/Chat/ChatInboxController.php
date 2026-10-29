<?php

namespace App\Http\Controllers\Web\Chat;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\DirectConversation;
use App\Models\Event;
use App\Models\User;
use App\Services\Chat\BuildUnifiedInboxService;
use App\Services\Events\Chat\ListEventMessagesService;
use App\Services\Events\Chat\MarkEventChatReadService;
use App\Services\Friends\DirectMessageService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatInboxController extends Controller
{
    public function index(
        Request $request,
        BuildUnifiedInboxService $inbox,
        DirectMessageService $dms,
        ListEventMessagesService $eventMessages,
        MarkEventChatReadService $eventReads,
    ): View {
        $user = $request->user();
        $bundle = $inbox->build($user);

        $selectedKey = trim((string) $request->query('c', ''));
        $typeFilter = $request->string('type')->toString();
        if (! in_array($typeFilter, ['all', 'direct', 'events', 'unread'], true)) {
            $typeFilter = 'all';
        }

        $panel = null;
        $selected = null;

        if (preg_match('/^dm-(\d+)$/', $selectedKey, $m)) {
            $conversation = DirectConversation::query()->find((int) $m[1]);
            if ($conversation !== null) {
                try {
                    $messages = $dms->listMessages($user, $conversation, 100);
                    $dms->markRead($user, $conversation);
                    $otherId = $conversation->otherUserId((int) $user->id);
                    $other = User::query()->findOrFail($otherId);
                    $panel = 'direct';
                    $selected = [
                        'key' => $selectedKey,
                        'conversation' => $conversation,
                        'messages' => $messages->reverse()->values(),
                        'other' => $other,
                    ];
                } catch (DomainException) {
                    $selectedKey = '';
                }
            }
        } elseif (preg_match('/^event-(\d+)$/', $selectedKey, $m)) {
            $event = Event::query()->find((int) $m[1]);
            if ($event !== null && $user->can('chat', $event)) {
                $messages = $eventMessages->list($user, $event, 50);
                $eventReads->mark($user, $event);
                $panel = 'event';
                $selected = [
                    'key' => $selectedKey,
                    'event' => $event->load(['category', 'city', 'creator:id,public_id,name,profile_image_path']),
                    'messages' => $messages->getCollection()->reverse()->values(),
                    'paginator' => $messages,
                ];
            } else {
                $selectedKey = '';
            }
        }

        return view('chats.index', [
            'items' => $bundle['items'],
            'eventUnread' => $bundle['event_unread'],
            'dmUnread' => $bundle['dm_unread'],
            'totalUnread' => $bundle['total_unread'],
            'typeFilter' => $typeFilter,
            'selectedKey' => $selectedKey,
            'panel' => $panel,
            'selected' => $selected,
            'categories' => Category::query()->active()->orderBy('sort_order')->orderBy('name_en')->get(),
        ]);
    }
}
