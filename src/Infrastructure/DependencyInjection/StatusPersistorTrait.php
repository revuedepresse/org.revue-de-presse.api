<?php
declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection;

use App\Infrastructure\Status\Persistor\StatusPersistorInterface;

trait StatusPersistorTrait
{
    private StatusPersistorInterface $statusPersistor;

    /**
     * @param StatusPersistorInterface $statusPersistor
     */
    public function setStatusPersistor(StatusPersistorInterface $statusPersistor): void
    {
        $this->statusPersistor = $statusPersistor;
    }
}