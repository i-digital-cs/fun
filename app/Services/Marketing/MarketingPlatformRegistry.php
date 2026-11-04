<?php

namespace App\Services\Marketing;

use App\Contracts\Marketing\MarketingPlatformInterface;
use App\Enums\Marketing\MarketingPlatformCode;
use App\Services\Marketing\Providers\MetaMarketingProvider;
use App\Services\Marketing\Providers\TikTokMarketingProvider;
use InvalidArgumentException;

class MarketingPlatformRegistry
{
    /** @var array<string, MarketingPlatformInterface> */
    private array $providers = [];

    public function __construct()
    {
        $this->register(new MetaMarketingProvider);
        $this->register(new TikTokMarketingProvider);
    }

    public function register(MarketingPlatformInterface $provider): void
    {
        $this->providers[$provider->platform()->value] = $provider;
    }

    public function get(MarketingPlatformCode|string $code): MarketingPlatformInterface
    {
        $key = $code instanceof MarketingPlatformCode ? $code->value : $code;

        if (! isset($this->providers[$key])) {
            throw new InvalidArgumentException("Unknown marketing provider [{$key}].");
        }

        return $this->providers[$key];
    }

    /**
     * @return list<MarketingPlatformInterface>
     */
    public function all(): array
    {
        return array_values($this->providers);
    }

    /**
     * @return list<array{code: string, name: string, enabled: bool, capabilities: list<string>, planned_capabilities: list<string>, runtime_ready: bool}>
     */
    public function summaries(): array
    {
        $config = config('marketing.providers', []);
        $out = [];

        foreach ($this->all() as $provider) {
            $code = $provider->platform()->value;
            $enabled = (bool) data_get($config, "{$code}.enabled", true);
            $runtime = array_map(static fn ($c) => $c->value, $provider->capabilities());
            $planned = array_map(static fn ($c) => $c->value, $provider->plannedCapabilities());
            $out[] = [
                'code' => $code,
                'name' => (string) data_get($config, "{$code}.display_name", $provider->platform()->displayName()),
                'enabled' => $enabled,
                'capabilities' => $runtime,
                'planned_capabilities' => $planned,
                'runtime_ready' => $runtime !== [],
            ];
        }

        return $out;
    }
}
