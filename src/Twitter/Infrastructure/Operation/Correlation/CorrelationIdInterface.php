<?php
declare (strict_types=1);

namespace App\Twitter\Infrastructure\Operation\Correlation;

interface CorrelationIdInterface
{
    public static function generate(): CorrelationIdInterface;

    public static function fromString(string $id): CorrelationIdInterface;

    public function asString(): string;

    public function __toString(): string;
}