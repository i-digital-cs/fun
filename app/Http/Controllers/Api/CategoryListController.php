<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryListController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $categories = Category::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name_en')
            ->get(['id', 'name_en', 'name_ar', 'slug', 'sort_order']);

        return response()->json([
            'categories' => $categories->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->localizedName(),
                'name_en' => $category->name_en,
                'name_ar' => $category->name_ar,
                'slug' => $category->slug,
                'sort_order' => $category->sort_order,
            ]),
        ]);
    }
}
