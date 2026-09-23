<?php

namespace App\Http\Controllers\Api\Auth;

use App\Enums\EmailAuthPurpose;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Services\Auth\EmailAuthCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function send(Request $request, EmailAuthCodeService $codes): JsonResponse
    {
        $user = $request->user();

        if ($user->email_verified_at !== null) {
            return response()->json([
                'message' => __('auth.email_verified'),
                'email_verified' => true,
            ]);
        }

        $codes->issueAndSend($user, EmailAuthPurpose::EmailVerify);

        return response()->json([
            'message' => __('auth.code_sent'),
            'email' => $user->email,
        ]);
    }

    public function verify(Request $request, EmailAuthCodeService $codes): JsonResponse
    {
        $user = $request->user();

        if ($user->email_verified_at !== null) {
            return response()->json([
                'message' => __('auth.email_verified'),
                'email_verified' => true,
                'user' => $this->userPayload($user),
            ]);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'size:4'],
        ]);

        try {
            $codes->verify($user, EmailAuthPurpose::EmailVerify, $validated['code']);
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => ['code' => [$exception->getMessage()]],
            ], 422);
        }

        $user->forceFill(['email_verified_at' => now()])->save();

        return response()->json([
            'message' => __('auth.email_verified'),
            'email_verified' => true,
            'user' => $this->userPayload($user->fresh()),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload($user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status->value,
            'locale' => $user->locale,
            'platform_role' => $user->platform_role?->value,
            'email_verified' => $user->email_verified_at !== null,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'needs_identity_documents' => $user->needsIdentityDocuments(),
        ];
    }
}
