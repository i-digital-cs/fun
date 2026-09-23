<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('city');

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status->value,
            'locale' => $user->locale,
            'platform_role' => $user->platform_role?->value,
            'city_id' => $user->city_id,
            'distance_km' => $user->distance_km,
            'profile_image_path' => $user->profile_image_path,
            'needs_identity_documents' => $user->needsIdentityDocuments(),
            'has_identity_documents' => $user->hasRequiredIdentityDocuments(),
            'identity_resubmit_required' => $user->identityResubmitRequired(),
            'city' => $user->city ? [
                'id' => $user->city->id,
                'name' => $user->city->localizedName(),
                'name_en' => $user->city->name_en,
                'name_ar' => $user->city->name_ar,
                'latitude' => $user->city->latitude,
                'longitude' => $user->city->longitude,
            ] : null,
        ]);
    }
}
