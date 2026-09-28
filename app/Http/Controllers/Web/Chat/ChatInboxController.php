<?php

namespace App\Http\Controllers\Web\Chat;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\Events\Chat\ListUserChatsService;
use App\Services\Friends\DirectMessageService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatInboxController extends Controller
{
    public function index(
        Request $request,
        ListUserChatsService $eventChats,
        DirectMessageService $dms,
    ): View {
        $user = $request->user();
        $tab = $request->string('tab')->toString() === 'direct' ? 'direct' : 'events';

        $eventList = $eventChats->list($user);
        $dmList = $dms->listConversations($user);

        return view('chats.index', [
            'tab' => $tab,
            'chats' => $eventList,
            'dms' => $dmList,
            'unreadTotal' => $eventChats->unreadTotal($user),
            'dmUnreadTotal' => (int) $dmList->sum('unread_count'),
            'categories' => Category::query()->active()->orderBy('sort_order')->orderBy('name_en')->get(),
        ]);
    }
}
