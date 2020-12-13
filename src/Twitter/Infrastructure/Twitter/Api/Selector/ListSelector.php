<?php
declare (strict_types=1);

namespace App\Twitter\Infrastructure\Twitter\Api\Selector;

use Ramsey\Uuid\UuidInterface;

interface ListSelector
{
    public const DEFAULT_CURSOR = '-1';

    public function __construct(
        UuidInterface $correlationId,
        string $screenName,
        string $cursor = '-1'
    );

    public function correlationId(): UuidInterface;

    public function screenName(): string;

    public function cursor(): string;

    public function isDefaultCursor(): bool;
}