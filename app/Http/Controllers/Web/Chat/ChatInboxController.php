<?php

namespace App\Http\Controllers\Web\Chat;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\Events\Chat\ListUserChatsService;
use Illuminate\View\View;

class ChatInboxController extends Controller
{
    public function index(ListUserChatsService $service): View
    {
        $user = request()->user();

        return view('chats.index', [
            'chats' => $service->list($user),
            'unreadTotal' => $service->unreadTotal($user),
            'categories' => Category::query()->active()->orderBy('sort_order')->orderBy('name_en')->get(),
        ]);
    }
}
