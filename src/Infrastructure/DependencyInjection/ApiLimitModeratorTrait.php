<?php

namespace App\Infrastructure\DependencyInjection;

use App\Api\Throttling\ApiLimitModeratorInterface;

trait ApiLimitModeratorTrait
{
    protected ApiLimitModeratorInterface $moderator;

    public function setModerator(ApiLimitModeratorInterface $moderator): self
    {
        $this->moderator = $moderator;

        return $this;
    }
}