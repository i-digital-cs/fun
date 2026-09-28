<?php

namespace App\Http\Controllers\Api\Account;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Services\Account\AccountDeletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountDeletionController extends Controller
{
    public function store(Request $request, AccountDeletionService $service): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['nullable', 'string'],
            'provider_reauth' => ['nullable', 'in:confirmed'],
        ]);

        try {
            $user = $service->requestDeletion(
                $request->user(),
                $validated['password'] ?? null,
                $validated['provider_reauth'] ?? null,
            );
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('account.deletion_requested', ['days' => $service->graceDays()]),
            'deletion_requested_at' => $user->deletion_requested_at?->toIso8601String(),
            'purge_after_days' => $service->graceDays(),
        ]);
    }

    public function destroy(Request $request, AccountDeletionService $service): JsonResponse
    {
        try {
            $user = $service->cancelDeletion($request->user());
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('account.deletion_cancelled'),
            'deletion_requested_at' => $user->deletion_requested_at,
        ]);
    }
}
