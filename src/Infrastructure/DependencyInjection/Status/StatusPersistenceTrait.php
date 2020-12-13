<?php
declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection\Status;

use App\Infrastructure\Publication\Persistence\StatusPersistenceInterface;

trait StatusPersistenceTrait
{
    private StatusPersistenceInterface $statusPersistence;

    /**
     * @param StatusPersistenceInterface $statusPersistence
     */
    public function setStatusPersistence(StatusPersistenceInterface $statusPersistence): void
    {
        $this->statusPersistence = $statusPersistence;
    }
}