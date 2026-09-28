<?php

namespace App\Http\Controllers\Web\Friends;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
use App\Services\Friends\FriendshipService;
use App\Services\Friends\SearchUsersService;
use App\Services\Profile\PublicProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FriendController extends Controller
{
    public function index(
        Request $request,
        FriendshipService $friends,
        PublicProfileService $profiles,
        SearchUsersService $search,
    ): View {
        $user = $request->user();
        $tab = $request->string('tab')->toString() === 'requests' ? 'requests' : 'friends';
        $q = $request->string('q')->toString();

        $friendList = $friends->listFriends($user)->map(fn (User $u) => $profiles->toPublicArray($u));
        $incoming = $friends->listIncomingRequests($user);
        $outgoing = $friends->listOutgoingRequests($user);
        $results = filled($q) ? $search->search($user, $q)->map(function (User $u) use ($user, $profiles, $friends) {
            $base = $profiles->toPublicArray($u);
            $state = $friends->stateFor($user, $u);

            return $base + ['friendship' => $state];
        }) : collect();

        return view('friends.index', [
            'tab' => $tab,
            'friends' => $friendList,
            'incoming' => $incoming,
            'outgoing' => $outgoing,
            'q' => $q,
            'results' => $results,
            'categories' => Category::query()->active()->orderBy('sort_order')->orderBy('name_en')->get(),
        ]);
    }

    public function store(Request $request, FriendshipService $friends): RedirectResponse
    {
        $validated = $request->validate([
            'public_id' => ['required', 'string', 'max:32'],
        ]);

        $target = User::query()->where('public_id', $validated['public_id'])->firstOrFail();

        try {
            $friends->request($request->user(), $target);
        } catch (DomainException $e) {
            return back()->withErrors(['public_id' => $e->getMessage()]);
        }

        return back()->with('status', __('friends.request_sent'));
    }

    public function accept(Request $request, User $user, FriendshipService $friends): RedirectResponse
    {
        try {
            $friends->accept($request->user(), $user);
        } catch (DomainException $e) {
            return back()->withErrors(['friend' => $e->getMessage()]);
        }

        return back()->with('status', __('friends.accepted'));
    }

    public function decline(Request $request, User $user, FriendshipService $friends): RedirectResponse
    {
        try {
            $friends->decline($request->user(), $user);
        } catch (DomainException $e) {
            return back()->withErrors(['friend' => $e->getMessage()]);
        }

        return back()->with('status', __('friends.declined'));
    }

    public function cancel(Request $request, User $user, FriendshipService $friends): RedirectResponse
    {
        try {
            $friends->cancel($request->user(), $user);
        } catch (DomainException $e) {
            return back()->withErrors(['friend' => $e->getMessage()]);
        }

        return back()->with('status', __('friends.request_cancelled'));
    }

    public function destroy(Request $request, User $user, FriendshipService $friends): RedirectResponse
    {
        try {
            $friends->remove($request->user(), $user);
        } catch (DomainException $e) {
            return back()->withErrors(['friend' => $e->getMessage()]);
        }

        return back()->with('status', __('friends.removed'));
    }
}
