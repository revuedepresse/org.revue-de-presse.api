<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Api;

use App\Twitter\Infrastructure\Http\Throttling\ApiLimitModeratorInterface;

trait ApiLimitModeratorTrait
{
    protected ApiLimitModeratorInterface $moderator;

    public function setModerator(ApiLimitModeratorInterface $moderator): self
    {
        $this->moderator = $moderator;

        return $this;
    }
}
