<?php
declare(strict_types=1);

namespace App\Infrastructure\Amqp\MessageBus;

use App\Amqp\Exception\InvalidListNameException;
use App\Api\Entity\TokenInterface;
use App\Domain\Collection\PublicationStrategyInterface;
use Closure;

interface PublicationMessageDispatcherInterface
{
    /**
     * @param PublicationStrategyInterface $strategy
     * @param TokenInterface               $token
     * @param Closure                      $writer
     *
     * @throws InvalidListNameException
     */
    public function dispatchPublicationMessages(
        PublicationStrategyInterface $strategy,
        TokenInterface $token,
        Closure $writer
    ): void;
}