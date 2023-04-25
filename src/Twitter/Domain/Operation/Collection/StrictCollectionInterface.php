<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Operation\Collection;

use Closure;

interface StrictCollectionInterface extends CollectionInterface
{
    public function count(): int;

    public function map(Closure $callable): self;

    public function add($item): self;

    /**
     * @param array $collection
     *
     * @return mixed
     */
    public static function fromArray(array $collection): self;

    /**
     * @return array
     */
    public function toArray(): array;
}
