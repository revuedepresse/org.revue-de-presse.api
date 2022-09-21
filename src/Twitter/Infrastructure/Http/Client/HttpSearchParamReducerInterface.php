<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\Client;

interface HttpSearchParamReducerInterface
{
    public function reduceParameters(string $endpoint, array $parameters): array;
}
