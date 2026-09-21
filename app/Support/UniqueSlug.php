<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class UniqueSlug
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public static function for(string $modelClass, string $value, ?int $ignoreId = null, string $fallback = 'item'): string
    {
        $base = Str::slug($value) ?: $fallback;
        $slug = $base;
        $i = 1;

        while (
            $modelClass::query()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
