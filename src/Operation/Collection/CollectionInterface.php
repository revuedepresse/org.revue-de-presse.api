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

    /**
     * @param callable $callable
     *
     * @return mixed
     */
    public function map(Closure $callable);

    /**
     * @param array $collection
     *
     * @return mixed
     */
    public static function fromArray(array $collection): self;
}