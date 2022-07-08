<?php

declare(strict_types=1);

namespace App\PublicationPopularity\Domain\Repository;

interface SearchParamsInterface
{
    public function getParams(): array;

    public function hasParam(string $name): bool;

    public function paramIs(string $name, $value): bool;
}
