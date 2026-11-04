<?php

namespace App\Exceptions\Marketing;

use App\Enums\Marketing\MarketingCapability;
use App\Enums\Marketing\MarketingPlatformCode;
use App\Exceptions\DomainException;

class UnsupportedMarketingCapabilityException extends DomainException
{
    public function __construct(
        public readonly MarketingPlatformCode $platform,
        public readonly MarketingCapability $capability,
    ) {
        parent::__construct(sprintf(
            'Marketing provider [%s] does not support capability [%s].',
            $platform->value,
            $capability->value,
        ));
    }
}
