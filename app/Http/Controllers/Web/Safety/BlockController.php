<?php

namespace App\Http\Controllers\Web\Safety;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
use App\Models\UserBlock;
use App\Services\Safety\UserBlockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlockController extends Controller
{
    public function index(Request $request): View
    {
        $blocks = UserBlock::query()
            ->with('blocked:id,name,email')
            ->where('blocker_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return view('safety.blocks', [
            'blocks' => $blocks,
            'categories' => Category::query()->active()->orderBy('sort_order')->orderBy('name_en')->get(),
        ]);
    }

    public function store(Request $request, User $user, UserBlockService $service): RedirectResponse
    {
        try {
            $service->block($request->user(), $user);
        } catch (DomainException $exception) {
            return back()->withErrors(['block' => $exception->getMessage()]);
        }

        return back()->with('status', __('safety.user_blocked'));
    }

    public function destroy(Request $request, User $user, UserBlockService $service): RedirectResponse
    {
        $service->unblock($request->user(), $user);

        return back()->with('status', __('safety.user_unblocked'));
    }
}
