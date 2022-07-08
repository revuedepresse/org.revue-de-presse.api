<?php
declare (strict_types=1);

namespace App\Twitter\Infrastructure\Http\Resource;

interface ResourceList
{
    public function getList(): array;

    public function count(): int;

    public function nextCursor(): string;

    public static function fromResponse(array $response): self;
}
