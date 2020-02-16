<?php
declare(strict_types=1);

namespace App\Operation\Collection;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @package App\Operation\Collection
 */
class Collection extends ArrayCollection implements CollectionInterface
{
    public static function fromArray(array $collection): CollectionInterface
    {
        return new self($collection);
    }
}