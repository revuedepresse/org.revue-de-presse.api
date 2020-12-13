<?php
declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection\Status;

use App\Domain\Status\Repository\LikedStatusRepositoryInterface;

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