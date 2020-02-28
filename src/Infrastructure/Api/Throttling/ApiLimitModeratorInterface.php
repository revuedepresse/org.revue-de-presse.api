<?php
declare(strict_types=1);

namespace App\Infrastructure\Api\Throttling;

interface ApiLimitModeratorInterface
{
    public function waitFor($seconds, array $parameters = []): void;
}