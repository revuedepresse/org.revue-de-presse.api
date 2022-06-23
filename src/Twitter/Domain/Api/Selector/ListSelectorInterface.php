<?php
declare (strict_types=1);

namespace App\Twitter\Domain\Api\Selector;

interface ListSelectorInterface
{
    public const DEFAULT_CURSOR = '-1';

    public function screenName(): string;

    public function cursor(): string;

    public function isDefaultCursor(): bool;
}