<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Status;

use App\Twitter\Infrastructure\Publication\Persistence\StatusPersistenceInterface;

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