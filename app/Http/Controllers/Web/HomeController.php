<?php

namespace App\Http\Controllers\Web;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();

        $categories = Category::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name_en')
            ->withCount([
                'events as published_events_count' => fn ($q) => $q
                    ->where('status', EventStatus::Published)
                    ->where('starts_at', '>=', now()->subDay()),
            ])
            ->get();

        if ($user !== null) {
            $user->loadMissing('city');
        }

        return view('home.index', [
            'appName' => site_display_name(),
            'categories' => $categories,
            'publishedTotal' => (int) $categories->sum('published_events_count'),
            'user' => $user,
        ]);
    }
}
