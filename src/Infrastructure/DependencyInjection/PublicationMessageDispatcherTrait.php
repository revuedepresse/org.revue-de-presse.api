<?php
declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection;

use App\Infrastructure\Amqp\MessageBus\PublicationMessageDispatcherInterface;

trait PublicationMessageDispatcherTrait
{
    /**
     * @var PublicationMessageDispatcherInterface
     */
    private PublicationMessageDispatcherInterface $publicationMessageDispatcher;

    public function setPublicationMessageDispatcher(PublicationMessageDispatcherInterface $publicationMessageDispatcher): self
    {
        $this->publicationMessageDispatcher = $publicationMessageDispatcher;

        return $this;
    }
}