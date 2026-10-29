<?php

namespace App\Http\Controllers\Web\Friends;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Resources\DirectMessageResource;
use App\Models\Category;
use App\Models\DirectConversation;
use App\Models\User;
use App\Services\Chat\ChatMediaService;
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
        ChatMediaService $media,
    ): RedirectResponse|\Illuminate\Http\JsonResponse {
        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:2000'],
            'client_uuid' => ['sometimes', 'nullable', 'string', 'max:36'],
            'reply_to_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'media' => ['sometimes', 'nullable', 'file', 'max:10240'],
            'media_kind' => ['sometimes', 'nullable', 'string', 'in:voice,image,file'],
            'duration_ms' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:120000'],
            'media_duration_ms' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:120000'],
        ]);

        $body = trim((string) ($validated['body'] ?? ''));
        if ($body === '' && ! $request->hasFile('media')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('chat.empty')], 422);
            }

            return back()->withErrors(['body' => __('chat.empty')]);
        }

        try {
            $existing = $dms->findExistingByClientUuid(
                $request->user(),
                $conversation,
                $validated['client_uuid'] ?? null
            );
            if ($existing !== null) {
                if ($request->expectsJson()) {
                    return (new DirectMessageResource($existing))->response()->setStatusCode(200);
                }

                return back();
            }

            $options = [
                'client_uuid' => $validated['client_uuid'] ?? null,
                'reply_to_id' => $validated['reply_to_id'] ?? null,
            ];
            if ($request->hasFile('media')) {
                $kind = (string) ($validated['media_kind'] ?? 'file');
                $durationMs = isset($validated['duration_ms'])
                    ? (int) $validated['duration_ms']
                    : (isset($validated['media_duration_ms']) ? (int) $validated['media_duration_ms'] : null);
                $stored = match ($kind) {
                    'voice' => $media->storeVoice(
                        $request->file('media'),
                        (int) $request->user()->id,
                        $durationMs
                    ),
                    'image' => $media->storeImage($request->file('media'), (int) $request->user()->id),
                    default => $media->storeFile($request->file('media'), (int) $request->user()->id),
                };
                $options = array_merge($options, [
                    'message_type' => $stored['message_type'],
                    'media_path' => $stored['path'],
                    'media_mime' => $stored['mime'],
                    'media_bytes' => $stored['bytes'],
                    'media_duration_ms' => $stored['duration_ms'],
                ]);
            }

            $message = $dms->sendToConversation(
                $request->user(),
                $conversation,
                $body,
                $options
            );
        } catch (DomainException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['body' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return (new DirectMessageResource($message))->response()->setStatusCode(201);
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
