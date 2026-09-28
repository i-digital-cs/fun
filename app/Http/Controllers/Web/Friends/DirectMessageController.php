<?php

namespace App\Http\Controllers\Web\Friends;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\DirectConversation;
use App\Models\User;
use App\Services\Friends\DirectMessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DirectMessageController extends Controller
{
    public function show(
        Request $request,
        DirectConversation $conversation,
        DirectMessageService $dms,
    ): View|RedirectResponse {
        try {
            $messages = $dms->listMessages($request->user(), $conversation, 100);
            $dms->markRead($request->user(), $conversation);
        } catch (DomainException $e) {
            abort(403, $e->getMessage());
        }

        $otherId = $conversation->otherUserId((int) $request->user()->id);
        $other = User::query()->findOrFail($otherId);

        return view('dms.show', [
            'conversation' => $conversation,
            'messages' => $messages->reverse()->values(),
            'other' => $other,
            'categories' => Category::query()->active()->orderBy('sort_order')->orderBy('name_en')->get(),
        ]);
    }

    public function store(
        Request $request,
        DirectConversation $conversation,
        DirectMessageService $dms,
    ): RedirectResponse {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $dms->sendToConversation($request->user(), $conversation, $validated['body']);
        } catch (DomainException $e) {
            return back()->withErrors(['body' => $e->getMessage()]);
        }

        return back();
    }

    public function open(Request $request, User $user, DirectMessageService $dms): RedirectResponse
    {
        try {
            $conversation = $dms->findOrCreateWith($request->user(), $user);
        } catch (DomainException $e) {
            return back()->withErrors(['friend' => $e->getMessage()]);
        }

        return redirect()->route('dms.show', $conversation);
    }
}
