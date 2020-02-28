<?php
declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection;

use App\Infrastructure\Api\Throttling\ApiLimitModeratorInterface;

trait ApiLimitModeratorTrait
{
    protected ApiLimitModeratorInterface $moderator;

    public function setModerator(ApiLimitModeratorInterface $moderator): self
    {
        $this->moderator = $moderator;

        return $this;
    }
}