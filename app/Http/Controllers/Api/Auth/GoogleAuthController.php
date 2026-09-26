<?php

namespace App\Http\Controllers\Api\Auth;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Services\Auth\GoogleAuthService;
use App\Services\Auth\GoogleIdTokenVerifier;
use App\Services\Auth\LoginUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    public function store(
        Request $request,
        GoogleIdTokenVerifier $verifier,
        GoogleAuthService $googleAuthService,
        LoginUserService $loginUserService,
    ): JsonResponse {
        $validated = $request->validate([
            'id_token' => ['nullable', 'string'],
            'access_token' => ['nullable', 'string'],
            'locale' => ['nullable', 'in:en,ar'],
        ]);

        if (! filled($validated['id_token'] ?? null) && ! filled($validated['access_token'] ?? null)) {
            throw ValidationException::withMessages([
                'id_token' => __('validation.required', ['attribute' => 'id_token']),
            ]);
        }

        try {
            if (filled($validated['id_token'] ?? null)) {
                $payload = $verifier->verify($validated['id_token']);
                $user = $googleAuthService->findOrCreateFromIdTokenPayload(
                    $payload,
                    $validated['locale'] ?? app()->getLocale()
                );
            } else {
                $googleUser = Socialite::driver('google')->userFromToken($validated['access_token']);

                if (! filled($googleUser->getEmail())) {
                    throw new DomainException(__('auth.google_invalid_token'));
                }

                $user = $googleAuthService->findOrCreateFromGoogle(
                    $googleUser,
                    $validated['locale'] ?? app()->getLocale()
                );
            }
        } catch (DomainException $exception) {
            Log::warning('google.auth.rejected', [
                'message' => $exception->getMessage(),
                'has_id_token' => filled($validated['id_token'] ?? null),
                'has_access_token' => filled($validated['access_token'] ?? null),
                'token_aud' => $this->peekJwtAud($validated['id_token'] ?? null),
                'accepted_audiences' => config('services.google.client_ids'),
            ]);

            return response()->json(['message' => $exception->getMessage()], 422);
        } catch (Throwable $exception) {
            report($exception);
            Log::warning('google.auth.exception', [
                'error' => $exception->getMessage(),
                'token_aud' => $this->peekJwtAud($validated['id_token'] ?? null),
                'accepted_audiences' => config('services.google.client_ids'),
            ]);

            return response()->json(['message' => __('auth.google_invalid_token')], 422);
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
            ],
        ]);
    }

    /**
     * Best-effort JWT `aud` peek for diagnostics (no signature check).
     */
    private function peekJwtAud(?string $jwt): ?string
    {
        if (! filled($jwt)) {
            return null;
        }

        $parts = explode('.', $jwt);
        if (count($parts) < 2) {
            return null;
        }

        try {
            $payload = json_decode(
                base64_decode(strtr($parts[1], '-_', '+/')) ?: '',
                true
            );
        } catch (Throwable) {
            return null;
        }

        if (! is_array($payload)) {
            return null;
        }

        $aud = $payload['aud'] ?? null;

        return is_string($aud) ? $aud : (is_array($aud) ? implode(',', $aud) : null);
    }
}
