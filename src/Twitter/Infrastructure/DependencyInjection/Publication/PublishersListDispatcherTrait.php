<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Publication;

use App\Twitter\Infrastructure\Amqp\MessageBus\PublishersListDispatcherInterface;

trait PublishersListDispatcherTrait
{
    private PublishersListDispatcherInterface $publishersListDispatcher;

    public function setPublishersListDispatcher(PublishersListDispatcherInterface $publishersListDispatcher): self
    {
        $this->publishersListDispatcher = $publishersListDispatcher;

        return $this;
    }

}