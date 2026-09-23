<?php

namespace App\Http\Controllers\Api\Auth;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Services\Auth\LoginUserService;
use App\Services\Auth\PasswordResetTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class PasswordResetController extends Controller
{
    public function request(Request $request, PasswordResetTokenService $service): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $service->requestCode($validated['email']);

        return response()->json([
            'message' => __('auth.reset_code_sent'),
        ]);
    }

    public function verify(Request $request, PasswordResetTokenService $service): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:4'],
        ]);

        try {
            $resetToken = $service->verifyCode($validated['email'], $validated['code']);
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => ['code' => [$exception->getMessage()]],
            ], 422);
        }

        return response()->json([
            'message' => __('auth.reset_code_accepted'),
            'reset_token' => $resetToken,
            'expires_in_minutes' => 15,
        ]);
    }

    public function reset(
        Request $request,
        PasswordResetTokenService $service,
        LoginUserService $loginUserService,
    ): JsonResponse {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'reset_token' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        try {
            $user = $service->resetWithToken(
                $validated['email'],
                $validated['reset_token'],
                $validated['password'],
            );
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $token = $loginUserService->issueApiToken($user);

        return response()->json([
            'message' => __('auth.password_reset'),
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status->value,
                'locale' => $user->locale,
                'platform_role' => $user->platform_role?->value,
                'email_verified' => $user->email_verified_at !== null,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'needs_identity_documents' => $user->needsIdentityDocuments(),
            ],
        ]);
    }
}
