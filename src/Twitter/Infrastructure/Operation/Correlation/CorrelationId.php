<?php

declare (strict_types=1);

namespace App\Twitter\Infrastructure\Operation\Correlation;

use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\UuidInterface;

class CorrelationId implements CorrelationIdInterface
{
    private UuidInterface $id;

    public function __construct(UuidInterface $id)
    {
        $this->id = $id;
    }

    public static function generate(): CorrelationIdInterface
    {
        return new self(UuidV4::uuid4());
    }

    public static function fromString(string $id): CorrelationIdInterface
    {
        return new self(UuidV4::fromString($id));
    }

    public function asString(): string
    {
        return (string) $this->id;
    }

    public function __toString(): string
    {
        return $this->asString();
    }
}