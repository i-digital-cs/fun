<?php

namespace App\Services\Catalog;

use App\Exceptions\DomainException;
use App\Models\Category;
use App\Support\UniqueSlug;
use Illuminate\Support\Facades\DB;

class ManageCategoryService
{
    /**
     * @param  array{name_en: string, name_ar: string, slug?: string|null, sort_order?: int, is_active?: bool}  $data
     */
    public function create(array $data): Category
    {
        return DB::transaction(function () use ($data) {
            return Category::query()->create([
                'name_en' => $data['name_en'],
                'name_ar' => $data['name_ar'],
                'slug' => UniqueSlug::for(Category::class, $data['slug'] ?? $data['name_en'], null, 'category'),
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'is_active' => $data['is_active'] ?? true,
            ]);
        });
    }

    /**
     * @param  array{name_en?: string, name_ar?: string, slug?: string|null, sort_order?: int, is_active?: bool}  $data
     */
    public function update(Category $category, array $data): Category
    {
        return DB::transaction(function () use ($category, $data) {
            if (array_key_exists('slug', $data) && filled($data['slug'])) {
                $data['slug'] = UniqueSlug::for(Category::class, (string) $data['slug'], $category->id, 'category');
            }

            $category->fill(collect($data)->only([
                'name_en',
                'name_ar',
                'slug',
                'sort_order',
                'is_active',
            ])->all())->save();

            return $category->refresh();
        });
    }

    public function deactivate(Category $category): Category
    {
        if (! $category->is_active) {
            throw new DomainException(__('catalog.category_already_inactive'));
        }

        $category->forceFill(['is_active' => false])->save();

        return $category->refresh();
    }

    public function activate(Category $category): Category
    {
        if ($category->is_active) {
            throw new DomainException(__('catalog.category_already_active'));
        }

        $category->forceFill(['is_active' => true])->save();

        return $category->refresh();
    }
}
