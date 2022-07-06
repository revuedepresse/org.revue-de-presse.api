<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\Throttling;

interface RateLimitComplianceInterface
{
    public function waitFor($seconds, array $parameters = []): void;
}