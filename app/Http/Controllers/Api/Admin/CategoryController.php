<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\Catalog\ManageCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Category::class);

        $categories = Category::query()
            ->orderBy('sort_order')
            ->orderBy('name_en')
            ->paginate(50);

        return response()->json($categories);
    }

    public function store(StoreCategoryRequest $request, ManageCategoryService $service): JsonResponse
    {
        $this->authorize('create', Category::class);

        $category = $service->create($request->validated());

        return response()->json(['category' => $category], 201);
    }

    public function update(
        UpdateCategoryRequest $request,
        Category $category,
        ManageCategoryService $service,
    ): JsonResponse {
        $this->authorize('update', $category);

        $category = $service->update($category, $request->validated());

        return response()->json(['category' => $category]);
    }

    public function deactivate(Category $category, ManageCategoryService $service): JsonResponse
    {
        $this->authorize('deactivate', $category);

        try {
            $category = $service->deactivate($category);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'category' => $category,
            'message' => __('catalog.category_deactivated'),
        ]);
    }

    public function activate(Category $category, ManageCategoryService $service): JsonResponse
    {
        $this->authorize('update', $category);

        try {
            $category = $service->activate($category);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'category' => $category,
            'message' => __('catalog.category_activated'),
        ]);
    }
}
