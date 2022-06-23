<?php

declare(strict_types=1);

namespace App\Twitter\Domain\Api;

interface SearchParamsInterface
{
    public function getParams(): array;

    public function hasParam(string $name): bool;

    public function paramIs(string $name, $value): bool;
}