<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Status;

use App\Twitter\Domain\Publication\Repository\LikedStatusRepositoryInterface;

trait LikedStatusRepositoryTrait
{
    private LikedStatusRepositoryInterface $likedStatusRepository;

    public function setLikedStatusRepository(
        LikedStatusRepositoryInterface $likedStatusRepository
    ): self
    {
        $this->likedStatusRepository = $likedStatusRepository;

        return $this;
    }
}