<?php

namespace App\Http\Controllers\Api\Auth;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Services\Auth\GoogleAuthService;
use App\Services\Auth\GoogleIdTokenVerifier;
use App\Services\Auth\LoginUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoogleAuthController extends Controller
{
    public function store(
        Request $request,
        GoogleIdTokenVerifier $verifier,
        GoogleAuthService $googleAuthService,
        LoginUserService $loginUserService,
    ): JsonResponse {
        $validated = $request->validate([
            'id_token' => ['required', 'string'],
            'locale' => ['nullable', 'in:en,ar'],
        ]);

        try {
            $payload = $verifier->verify($validated['id_token']);
            $user = $googleAuthService->findOrCreateFromIdTokenPayload(
                $payload,
                $validated['locale'] ?? app()->getLocale()
            );
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $token = $loginUserService->issueApiToken($user, 'google');

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'email_verified' => $user->email_verified_at !== null,
            'needs_email_verification' => $user->email_verified_at === null,
            'needs_identity_documents' => $user->needsIdentityDocuments(),
            'can_participate' => $user->canParticipate(),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status->value,
                'locale' => $user->locale,
                'platform_role' => $user->platform_role?->value,
                'profile_image_path' => $user->profile_image_path,
                'has_password' => filled($user->getAuthPassword()),
                'email_verified' => $user->email_verified_at !== null,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'needs_identity_documents' => $user->needsIdentityDocuments(),
                'has_identity_documents' => $user->hasRequiredIdentityDocuments(),
                'can_participate' => $user->canParticipate(),
            ],
        ]);
    }
}
