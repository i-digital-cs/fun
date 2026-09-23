<?php

namespace App\Http\Controllers\Api\Auth;

use App\Enums\EmailAuthPurpose;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\EmailAuthCodeService;
use App\Services\Auth\LoginUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function store(
        LoginRequest $request,
        LoginUserService $loginUserService,
        EmailAuthCodeService $codes,
    ): JsonResponse {
        try {
            $user = $loginUserService->authenticate($request->validated());
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $token = $loginUserService->issueApiToken($user);
        $emailVerified = $user->email_verified_at !== null;

        if (! $emailVerified) {
            $codes->issueAndSend($user, EmailAuthPurpose::EmailVerify);
        }

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'email_verified' => $emailVerified,
            'needs_email_verification' => ! $emailVerified,
            'user' => [
                'id' => $user->id,
                'public_id' => $user->public_id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status->value,
                'locale' => $user->locale,
                'platform_role' => $user->platform_role?->value,
                'email_verified' => $emailVerified,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'needs_identity_documents' => $user->needsIdentityDocuments(),
            ],
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => __('auth.logged_out')]);
    }
}
