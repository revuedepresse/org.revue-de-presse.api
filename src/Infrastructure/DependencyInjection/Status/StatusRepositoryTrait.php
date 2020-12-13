<?php
declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection\Status;

use App\Domain\Status\Repository\StatusRepositoryInterface;

trait StatusRepositoryTrait
{
    private StatusRepositoryInterface $statusRepository;

    public function setStatusRepository(StatusRepositoryInterface $statusRepository): self
    {
        $this->statusRepository = $statusRepository;

        return $this;
    }
}