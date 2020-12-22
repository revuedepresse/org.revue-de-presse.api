<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\MessageBus;

use App\Twitter\Infrastructure\Amqp\Exception\InvalidListNameException;
use App\Twitter\Infrastructure\Api\Entity\TokenInterface;
use App\Twitter\Domain\Curation\PublicationStrategyInterface;
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