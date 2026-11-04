<?php

namespace App\Http\Controllers\Web\Friends;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Resources\DirectMessageResource;
use App\Models\DirectConversation;
use App\Models\User;
use App\Services\Chat\ChatMediaService;
use App\Services\Friends\DirectMessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DirectMessageController extends Controller
{
    /**
     * Legacy /dms/{id} → canonical Chat V2 workspace.
     */
    public function show(
        Request $request,
        DirectConversation $conversation,
        DirectMessageService $dms,
    ): RedirectResponse {
        try {
            $dms->assertParticipant($request->user(), $conversation);
        } catch (DomainException $e) {
            abort(403, $e->getMessage());
        }

        return redirect()->to(chat_dm_url($conversation));
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
            'media' => ['sometimes', 'nullable', 'file', 'max:51200'],
            'media_kind' => ['sometimes', 'nullable', 'string', 'in:voice,image,video,file'],
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
                $scopeId = (int) $conversation->id;
                $stored = match ($kind) {
                    'voice' => $media->storeVoice(
                        $request->file('media'),
                        (int) $request->user()->id,
                        $durationMs,
                        $scopeId,
                        'direct',
                    ),
                    'image' => $media->storeImage($request->file('media'), (int) $request->user()->id, $scopeId, 'direct'),
                    'video' => $media->storeVideo($request->file('media'), (int) $request->user()->id, $scopeId, 'direct'),
                    default => $media->storeFile($request->file('media'), (int) $request->user()->id, $scopeId, 'direct'),
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

        return redirect()->to(chat_dm_url($conversation));
    }

    public function open(Request $request, User $user, DirectMessageService $dms): RedirectResponse
    {
        try {
            $conversation = $dms->findOrCreateWith($request->user(), $user);
        } catch (DomainException $e) {
            return back()->withErrors(['friend' => $e->getMessage()]);
        }

        return redirect()->to(chat_dm_url($conversation));
    }
}
