<?php

namespace App\Services\Location;

use App\Models\City;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Searchable location picker over the existing cities table (EN/AR + aliases).
 * Single source of truth for Flutter + Web — no third-party geocoder.
 */
class SearchCitiesService
{
    /**
     * @return Collection<int, City>
     */
    public function search(string $query, int $limit = 12): Collection
    {
        $q = trim($query);
        if ($q === '' || mb_strlen($q) < 1) {
            return City::query()
                ->active()
                ->orderBy('name_en')
                ->limit($limit)
                ->get();
        }

        $normalized = $this->normalize($q);
        $tokens = $this->tokensFor($normalized, $q);

        return City::query()
            ->active()
            ->where(function ($builder) use ($tokens, $q) {
                foreach ($tokens as $token) {
                    $like = '%'.$token.'%';
                    $builder->orWhere('name_en', 'like', $like)
                        ->orWhere('name_ar', 'like', $like)
                        ->orWhere('slug', 'like', '%'.Str::slug($token).'%');
                }
                // Also match raw user input (Arabic digits / mixed scripts).
                $builder->orWhere('name_en', 'like', '%'.$q.'%')
                    ->orWhere('name_ar', 'like', '%'.$q.'%');
            })
            ->orderBy('name_en')
            ->limit($limit)
            ->get();
    }

    /**
     * @return list<string>
     */
    private function tokensFor(string $normalized, string $raw): array
    {
        $aliases = config('location.search_aliases', []);
        $tokens = [$normalized, $raw];

        foreach ($aliases as $alias => $canonical) {
            $aliasNorm = $this->normalize((string) $alias);
            if ($aliasNorm !== '' && (
                $normalized === $aliasNorm
                || str_contains($normalized, $aliasNorm)
                || str_contains($aliasNorm, $normalized)
            )) {
                $tokens[] = (string) $canonical;
                $tokens[] = $aliasNorm;
            }
        }

        return array_values(array_unique(array_filter(
            array_map(fn ($t) => trim((string) $t), $tokens),
            fn ($t) => $t !== ''
        )));
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace(['أ', 'إ', 'آ'], 'ا', $value);
        $value = str_replace(['ى'], 'ي', $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return $value;
    }
}
