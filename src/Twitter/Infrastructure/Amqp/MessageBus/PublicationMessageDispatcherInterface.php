<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\MessageBus;

use App\Twitter\Infrastructure\Amqp\Exception\InvalidListNameException;
use App\Twitter\Domain\Api\Model\TokenInterface;
use App\Twitter\Domain\Curation\CurationStrategyInterface;
use Closure;

interface PublicationMessageDispatcherInterface
{
    /**
     * @param CurationStrategyInterface $strategy
     * @param TokenInterface               $token
     * @param Closure                      $writer
     *
     * @throws InvalidListNameException
     */
    public function dispatchPublicationMessages(
        CurationStrategyInterface $strategy,
        TokenInterface            $token,
        Closure                   $writer
    ): void;
}