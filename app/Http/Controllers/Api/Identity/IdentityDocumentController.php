<?php

namespace App\Http\Controllers\Api\Identity;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Identity\StoreIdentityDocumentsRequest;
use App\Services\Identity\SubmitIdentityDocumentsService;
use Illuminate\Http\JsonResponse;

class IdentityDocumentController extends Controller
{
    public function store(
        StoreIdentityDocumentsRequest $request,
        SubmitIdentityDocumentsService $service,
    ): JsonResponse {
        try {
            $user = $service->submit($request->user(), $request->validated());
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'message' => __('identity.activated'),
            'user' => [
                'id' => $user->id,
                'status' => $user->status->value,
            ],
        ]);
    }
}
