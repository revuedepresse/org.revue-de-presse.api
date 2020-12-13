<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection;

use Symfony\Component\Messenger\MessageBusInterface;

trait MessageBusTrait
{
    /**
     * @var MessageBusInterface
     */
    private MessageBusInterface $dispatcher;

    /**
     * @param $messageBus
     *
     * @return $this
     */
    public function setMessageBus(MessageBusInterface $messageBus): self
    {
        $this->dispatcher = $messageBus;

        return $this;
    }

}