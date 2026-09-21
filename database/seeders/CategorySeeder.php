<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name_en' => 'Football', 'name_ar' => 'كرة القدم', 'sort_order' => 10],
            ['name_en' => 'PlayStation', 'name_ar' => 'بلايستيشن', 'sort_order' => 20],
            ['name_en' => 'Basketball', 'name_ar' => 'كرة السلة', 'sort_order' => 30],
            ['name_en' => 'Running', 'name_ar' => 'جري', 'sort_order' => 40],
            ['name_en' => 'Gaming', 'name_ar' => 'ألعاب إلكترونية', 'sort_order' => 50],
            ['name_en' => 'Board Games', 'name_ar' => 'ألعاب طاولة', 'sort_order' => 60],
            ['name_en' => 'Cinema', 'name_ar' => 'سينما', 'sort_order' => 70],
            ['name_en' => 'Social', 'name_ar' => 'اجتماعي', 'sort_order' => 80],
            ['name_en' => 'Outdoor', 'name_ar' => 'أنشطة خارجية', 'sort_order' => 90],
            ['name_en' => 'Tennis', 'name_ar' => 'تنس', 'sort_order' => 100],
        ];

        foreach ($categories as $category) {
            Category::query()->updateOrCreate(
                ['slug' => Str::slug($category['name_en'])],
                [
                    'name_en' => $category['name_en'],
                    'name_ar' => $category['name_ar'],
                    'sort_order' => $category['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
