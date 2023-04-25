<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Status;

use App\Twitter\Domain\Publication\Repository\StatusRepositoryInterface;

trait StatusRepositoryTrait
{
    private StatusRepositoryInterface $statusRepository;

    public function setStatusRepository(StatusRepositoryInterface $statusRepository): self
    {
        $this->statusRepository = $statusRepository;

        return $this;
    }
}