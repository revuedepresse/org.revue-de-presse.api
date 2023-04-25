<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Operation\Collection;

use Closure;

interface CollectionInterface
{
    public function count();

    public function map(Closure $callable);

    public function add($item);

    public function isEmpty();

    public function isNotEmpty(): bool;

    public static function fromArray(array $collection): self;

    public function toArray();

    public function first();
}
