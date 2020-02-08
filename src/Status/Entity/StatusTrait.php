<?php

namespace App\Status\Entity;

use WeavingTheWeb\Bundle\ApiBundle\Entity\Aggregate;

trait StatusTrait
{
    public function addToAggregates(Aggregate $aggregate)
    {
        $this->aggregates->add($aggregate);
    }
}
