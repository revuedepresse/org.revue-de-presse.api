<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Operation\Collection;

use Closure;

interface CollectionInterface
{
    /**
     * @return int
     */
    public function count();

    public function map(Closure $callable);

    public function add($item);

    public function isEmpty();

    public function isNotEmpty(): bool;

    /**
     * @param array $collection
     *
     * @return mixed
     */
    public static function fromArray(array $collection): self;

    /**
     * @return array
     */
    public function toArray();

    public function first();
}