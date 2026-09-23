<?php

namespace App\Http\Controllers\Api\Auth;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Services\Auth\GoogleAuthService;
use App\Services\Auth\LoginUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleLoginController extends Controller
{
    public function store(
        Request $request,
        GoogleAuthService $googleAuth,
        LoginUserService $loginUserService,
    ): JsonResponse {
        $validated = $request->validate([
            'access_token' => ['required', 'string'],
            'locale' => ['sometimes', 'nullable', 'string', 'in:en,ar'],
        ]);

        try {
            $googleUser = Socialite::driver('google')->userFromToken($validated['access_token']);
        } catch (Throwable) {
            return response()->json(['message' => __('auth.failed')], 422);
        }

        if (! filled($googleUser->getEmail())) {
            return response()->json(['message' => __('auth.failed')], 422);
        }

        try {
            $user = $googleAuth->findOrCreateFromGoogle(
                $googleUser,
                $validated['locale'] ?? null,
            );
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $token = $loginUserService->issueApiToken($user, 'mobile-google');

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status->value,
                'locale' => $user->locale,
                'platform_role' => $user->platform_role?->value,
                'city_id' => $user->city_id,
                'distance_km' => $user->distance_km,
            ],
        ]);
    }
}
