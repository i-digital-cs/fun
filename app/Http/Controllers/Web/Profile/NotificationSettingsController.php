<?php

namespace App\Http\Controllers\Web\Profile;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        return view('settings.notifications', [
            'user' => $request->user(),
            'categories' => Category::query()->active()->orderBy('sort_order')->orderBy('name_en')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'notify_friend_events' => ['sometimes', 'boolean'],
        ]);

        $request->user()->forceFill([
            'notify_friend_events' => $request->boolean('notify_friend_events'),
        ])->save();

        return back()->with('status', __('friends.saved'));
    }
}
