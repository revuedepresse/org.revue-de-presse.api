<?php

namespace App\Status\Entity;

use App\Api\Entity\Aggregate;

trait StatusTrait
{
    public function addToAggregates(Aggregate $aggregate)
    {
        $this->aggregates->add($aggregate);
    }
}
