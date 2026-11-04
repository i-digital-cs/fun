<?php

namespace App\Repositories\Marketing;

use App\Models\Marketing\MarketingPlatform;
use Illuminate\Support\Collection;

class MarketingPlatformRepository
{
    /**
     * @return Collection<int, MarketingPlatform>
     */
    public function allOrdered(): Collection
    {
        return MarketingPlatform::query()->orderBy('id')->get();
    }

    public function findByCode(string $code): ?MarketingPlatform
    {
        return MarketingPlatform::query()->where('code', $code)->first();
    }

    /**
     * @param  array{code: string, name: string, is_enabled?: bool, capabilities_json?: list<string>|null}  $attributes
     */
    public function upsertByCode(array $attributes): MarketingPlatform
    {
        return MarketingPlatform::query()->updateOrCreate(
            ['code' => $attributes['code']],
            [
                'name' => $attributes['name'],
                'is_enabled' => $attributes['is_enabled'] ?? true,
                'capabilities_json' => $attributes['capabilities_json'] ?? null,
            ],
        );
    }
}
