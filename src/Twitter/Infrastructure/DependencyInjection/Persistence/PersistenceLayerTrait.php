<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Persistence;

use App\Twitter\Domain\Persistence\PersistenceLayerInterface;

trait PersistenceLayerTrait
{
    private PersistenceLayerInterface $persistenceLayer;

    public function setPersistenceLayer(PersistenceLayerInterface $publicationPersistence): void
    {
        $this->persistenceLayer = $publicationPersistence;
    }
}
