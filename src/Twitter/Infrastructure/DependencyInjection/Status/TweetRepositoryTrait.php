<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Status;

use App\Twitter\Domain\Publication\Repository\TweetRepositoryInterface;

trait TweetRepositoryTrait
{
    private TweetRepositoryInterface $statusRepository;

    public function setStatusRepository(TweetRepositoryInterface $statusRepository): self
    {
        $this->statusRepository = $statusRepository;

        return $this;
    }
}