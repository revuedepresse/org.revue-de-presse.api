<?php
declare(strict_types=1);

namespace App\Infrastructure\MessageDispatcher;

use Symfony\Component\Messenger\MessageBusInterface;

class PublicationDispatcher
{
    /**
     * @var MessageBusInterface
     */
    private MessageBusInterface $fetchStatusDispatcher;

    /**
     * @var MessageBusInterface
     */
    private MessageBusInterface $fetchLikesDispatcher;

    public function __construct(
        MessageBusInterface $fetchStatusDispatcher,
        MessageBusInterface $fetchLikesDispatcher
    ) {
        $this->fetchStatusDispatcher = $fetchStatusDispatcher;
        $this->fetchLikesDispatcher = $fetchLikesDispatcher;
    }

    public function fetchLikesDispatcher(): MessageBusInterface
    {
        return $this->fetchLikesDispatcher;
    }

    public function fetchStatusDispatcher(): MessageBusInterface
    {
        return $this->fetchStatusDispatcher;
    }
}