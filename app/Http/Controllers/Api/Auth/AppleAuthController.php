<?php

namespace App\Http\Controllers\Api\Auth;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Services\Auth\AppleAuthService;
use App\Services\Auth\AppleIdTokenVerifier;
use App\Services\Auth\LoginUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class AppleAuthController extends Controller
{
    public function store(
        Request $request,
        AppleIdTokenVerifier $verifier,
        AppleAuthService $appleAuthService,
        LoginUserService $loginUserService,
    ): JsonResponse {
        $validated = $request->validate([
            'id_token' => ['required', 'string'],
            'name' => ['nullable', 'string', 'max:120'],
            'locale' => ['nullable', 'in:en,ar'],
        ]);

        try {
            $payload = $verifier->verify($validated['id_token']);
            if (filled($validated['name'] ?? null)) {
                $payload['name'] = $validated['name'];
            }
            $user = $appleAuthService->findOrCreateFromIdTokenPayload(
                $payload,
                $validated['locale'] ?? app()->getLocale()
            );
        } catch (DomainException $exception) {
            Log::warning('apple.auth.rejected', ['message' => $exception->getMessage()]);

            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => __('auth.apple_invalid_token')], 422);
        }

        $token = $loginUserService->issueApiToken($user, 'apple');

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'email_verified' => $user->email_verified_at !== null,
            'needs_email_verification' => $user->email_verified_at === null,
            'needs_identity_documents' => $user->needsIdentityDocuments(),
            'can_participate' => $user->canParticipate(),
            'user' => [
                'id' => $user->id,
                'public_id' => $user->public_id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status->value,
                'locale' => $user->locale,
                'sex' => $user->sex?->value,
                'needs_sex' => $user->needsSex(),
                'platform_role' => $user->platform_role?->value,
                'profile_image_path' => $user->profile_image_path,
                'city_id' => $user->city_id,
                'distance_km' => $user->distance_km,
                'has_password' => filled($user->getAuthPassword()),
                'email_verified' => $user->email_verified_at !== null,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'needs_identity_documents' => $user->needsIdentityDocuments(),
                'has_identity_documents' => $user->hasRequiredIdentityDocuments(),
                'can_participate' => $user->canParticipate(),
                'deletion_requested_at' => $user->deletion_requested_at?->toIso8601String(),
            ],
        ]);
    }
}
