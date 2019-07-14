<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Entity;

final class AggregateIdentity
{
    private $id;

    /**
     * @param int $id
     */
    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function __toString()
    {
        return (string) $this->id;
    }
}
