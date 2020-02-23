<?php
declare(strict_types=1);

namespace App\Operation\Collection;

use Closure;

interface CollectionInterface
{
    /**
     * @return int
     */
    public function count();

    public function map(Closure $callable);


    public function isEmpty(): bool;

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
}