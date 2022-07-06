<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Http;

use App\Twitter\Infrastructure\Http\Throttling\RateLimitComplianceInterface;

trait RateLimitComplianceTrait
{
    protected RateLimitComplianceInterface $rateLimitCompliance;

    public function setRateLimitCompliance(RateLimitComplianceInterface $rateLimitCompliance): self
    {
        $this->rateLimitCompliance = $rateLimitCompliance;

        return $this;
    }
}