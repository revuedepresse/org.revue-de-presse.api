<?php

namespace App\Twitter\Infrastructure\Publication\Mapping;

use App\Twitter\Infrastructure\Http\Entity\Tweet;

class IdentityMapping implements MappingAwareInterface
{
    public function apply(Tweet $status): Tweet {
        return $status;
    }
}
