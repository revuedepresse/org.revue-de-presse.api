<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\MessageBus;

use App\Twitter\Domain\Http\Model\TokenInterface;
use App\Twitter\Domain\Curation\CurationRulesetInterface;
use Closure;
use Doctrine\DBAL\Exception;

interface FetchTweetsAmqpMessagesDispatcherInterface
{
    /**
     * @throws Exception
     */
    public function dispatchFetchTweetsMessages(
        CurationRulesetInterface $ruleset,
        TokenInterface           $token,
        Closure                  $writer
    ): void;
}
