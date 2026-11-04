<?php

namespace App\Repositories\Marketing;

use App\Models\Marketing\MarketingAccount;
use Illuminate\Support\Collection;

class MarketingAccountRepository
{
    /**
     * @return Collection<int, MarketingAccount>
     */
    public function allWithPlatform(): Collection
    {
        return MarketingAccount::query()
            ->with('platform')
            ->orderBy('id')
            ->get();
    }

    public function countConnected(): int
    {
        return MarketingAccount::query()->count();
    }
}
