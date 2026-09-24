<?php

namespace App\Http\Controllers\Web\Profile;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\Profile\PublicProfileService;
use App\Support\Seo;
use Illuminate\View\View;

class PublicProfileController extends Controller
{
    public function show(string $publicId, PublicProfileService $profiles): View
    {
        $user = $profiles->findByPublicId($publicId);
        $profile = $profiles->toPublicArray($user);

        $hostedEvents = $user->createdEvents()
            ->with(['category', 'city'])
            ->withCount('members')
            ->where('status', EventStatus::Published)
            ->latest('starts_at')
            ->limit(6)
            ->get();

        return view('profile.public', [
            'profileUser' => $user,
            'profile' => $profile,
            'hostedEvents' => $hostedEvents,
            'categories' => Category::query()->active()->orderBy('sort_order')->orderBy('name_en')->get(),
            'seo' => Seo::forPublicProfile($profile, $publicId),
        ]);
    }
}
