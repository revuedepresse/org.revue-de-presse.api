<?php

namespace App\Twitter\Infrastructure\Publication\Mapping;

use App\Twitter\Infrastructure\Http\Entity\Tweet;

interface MappingAwareInterface
{
    /**
     * @param Tweet $status
     * @return Tweet
     */
    public function apply(Tweet $status): Tweet;
}
