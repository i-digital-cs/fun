<?php

namespace App\Http\Controllers\Web\Profile;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\Friends\FriendshipService;
use App\Services\Profile\PublicProfileService;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicProfileController extends Controller
{
    public function show(
        Request $request,
        string $publicId,
        PublicProfileService $profiles,
        FriendshipService $friends,
    ): View {
        $user = $profiles->findByPublicId($publicId);
        $profile = $profiles->toPublicArray($user);

        $hostedEvents = $user->createdEvents()
            ->with(['category', 'city'])
            ->withCount('members')
            ->where('status', EventStatus::Published)
            ->latest('starts_at')
            ->limit(6)
            ->get();

        $friendship = null;
        if ($request->user()) {
            $friendship = $friends->stateFor($request->user(), $user);
        }

        return view('profile.public', [
            'profileUser' => $user,
            'profile' => $profile,
            'friendship' => $friendship,
            'hostedEvents' => $hostedEvents,
            'categories' => Category::query()->active()->orderBy('sort_order')->orderBy('name_en')->get(),
            'seo' => Seo::forPublicProfile($profile, $publicId),
        ]);
    }
}
