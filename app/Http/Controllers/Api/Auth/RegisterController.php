<?php

namespace App\Http\Controllers\Api\Auth;

use App\Enums\EmailAuthPurpose;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\Auth\EmailAuthCodeService;
use App\Services\Auth\LoginUserService;
use App\Services\Auth\RegisterUserService;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    public function store(
        RegisterRequest $request,
        RegisterUserService $registerUserService,
        LoginUserService $loginUserService,
        EmailAuthCodeService $codes,
    ): JsonResponse {
        $user = $registerUserService->register($request->validated());
        $codes->issueAndSend($user, EmailAuthPurpose::EmailVerify);
        $token = $loginUserService->issueApiToken($user);

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'email_verified' => false,
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
                'email_verified_at' => null,
            ],
        ], 201);
    }
}
