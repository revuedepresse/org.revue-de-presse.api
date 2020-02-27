<?php

namespace App\Api\Throttling;

interface ApiLimitModeratorInterface
{
    public function waitFor($seconds, array $parameters = []): void;
}