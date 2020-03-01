<?php
declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection\Publication;

use App\Infrastructure\Amqp\MessageBus\PublicationListDispatcherInterface;

trait PublicationListDispatcherTrait
{
    private PublicationListDispatcherInterface $publicationListDispatcher;

    public function setPublicationListDispatcher(PublicationListDispatcherInterface $publicationListDispatcher): self
    {
        $this->publicationListDispatcher = $publicationListDispatcher;

        return $this;
    }

}