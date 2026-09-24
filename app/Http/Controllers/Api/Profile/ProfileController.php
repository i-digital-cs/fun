<?php

namespace App\Http\Controllers\Api\Profile;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Services\Profile\UpdateProfileService;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function update(
        UpdateProfileRequest $request,
        UpdateProfileService $service,
    ): JsonResponse {
        try {
            $result = $service->update($request->user(), $request->validated());
        } catch (DomainException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => [
                    ($exception->getMessage() === __('profile.current_password_invalid')
                        ? 'current_password'
                        : 'email') => [$exception->getMessage()],
                ],
            ], 422);
        }

        $user = $result['user'];

        return response()->json([
            'message' => $result['email_changed']
                ? __('profile.email_changed_verify')
                : __('profile.saved'),
            'email_changed' => $result['email_changed'],
            'email_verified' => $user->email_verified_at !== null,
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
                'email_verified' => $user->email_verified_at !== null,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'needs_identity_documents' => $user->needsIdentityDocuments(),
            ],
        ]);
    }
}
