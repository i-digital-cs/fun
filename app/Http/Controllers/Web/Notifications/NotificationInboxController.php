<?php

namespace App\Http\Controllers\Web\Notifications;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\Notifications\NotificationInboxService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationInboxController extends Controller
{
    public function index(NotificationInboxService $inbox): View
    {
        $user = request()->user();

        return view('notifications.index', [
            'notifications' => $inbox->paginate($user, 30),
            'unreadCount' => $inbox->unreadCount($user),
            'categories' => Category::query()->active()->orderBy('sort_order')->orderBy('name_en')->get(),
        ]);
    }

    public function markRead(string $notification, NotificationInboxService $inbox): RedirectResponse
    {
        $item = $inbox->markRead(request()->user(), $notification);
        if ($item === null) {
            abort(404);
        }

        $url = data_get($item->data, 'url');

        return filled($url)
            ? redirect()->to($url)
            : redirect()->route('notifications.index');
    }

    public function markAllRead(NotificationInboxService $inbox): RedirectResponse
    {
        $inbox->markAllRead(request()->user());

        return redirect()
            ->route('notifications.index')
            ->with('status', __('notifications.marked_all_read'));
    }
}
