<?php

namespace App\Http\Controllers\Web\Admin;

use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\Catalog\ManageCategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Category::class);

        $categories = Category::query()
            ->orderBy('sort_order')
            ->orderBy('name_en')
            ->paginate(30);

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        $this->authorize('create', Category::class);

        return view('admin.categories.create');
    }

    public function store(StoreCategoryRequest $request, ManageCategoryService $service): RedirectResponse
    {
        $this->authorize('create', Category::class);

        $service->create($request->validated());

        return redirect()->route('admin.categories.index')
            ->with('status', __('catalog.category_created'));
    }

    public function edit(Category $category): View
    {
        $this->authorize('update', $category);

        return view('admin.categories.edit', compact('category'));
    }

    public function update(
        UpdateCategoryRequest $request,
        Category $category,
        ManageCategoryService $service,
    ): RedirectResponse {
        $this->authorize('update', $category);

        $service->update($category, $request->validated());

        return redirect()->route('admin.categories.index')
            ->with('status', __('catalog.category_updated'));
    }

    public function deactivate(Category $category, ManageCategoryService $service): RedirectResponse
    {
        $this->authorize('deactivate', $category);

        try {
            $service->deactivate($category);
        } catch (DomainException $exception) {
            return back()->withErrors(['category' => $exception->getMessage()]);
        }

        return back()->with('status', __('catalog.category_deactivated'));
    }

    public function activate(Category $category, ManageCategoryService $service): RedirectResponse
    {
        $this->authorize('update', $category);

        try {
            $service->activate($category);
        } catch (DomainException $exception) {
            return back()->withErrors(['category' => $exception->getMessage()]);
        }

        return back()->with('status', __('catalog.category_activated'));
    }
}
