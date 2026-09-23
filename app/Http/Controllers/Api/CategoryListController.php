<?php

namespace App\Http\Controllers\Api;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryListController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $categories = Category::query()
            ->active()
            ->withCount([
                'events as published_events_count' => fn ($q) => $q->where('status', EventStatus::Published),
            ])
            ->orderBy('sort_order')
            ->orderBy('name_en')
            ->get();

        return response()->json([
            'categories' => $categories->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->localizedName(),
                'name_en' => $category->name_en,
                'name_ar' => $category->name_ar,
                'slug' => $category->slug,
                'sort_order' => $category->sort_order,
                'published_events_count' => (int) $category->published_events_count,
            ]),
        ]);
    }
}
