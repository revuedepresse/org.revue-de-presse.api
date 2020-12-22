<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Collection;

use App\Twitter\Infrastructure\Twitter\Collector\LikedStatusCollectDeciderInterface;

trait LikedStatusCollectDecider
{
    private LikedStatusCollectDeciderInterface $likedStatusCollectDecider;

    public function setLikedStatusCollectDecider(LikedStatusCollectDeciderInterface $likedStatusCollectDecider): self
    {
        $this->likedStatusCollectDecider = $likedStatusCollectDecider;

        return $this;
    }
}