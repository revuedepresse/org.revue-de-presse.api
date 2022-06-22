<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\MessageBus;

use App\Twitter\Domain\Api\Model\TokenInterface;
use App\Twitter\Domain\Curation\CurationRulesetInterface;
use Closure;

interface FetchTweetsMessageDispatcherInterface
{
    public function dispatchFetchTweetsMessages(
        CurationRulesetInterface $ruleset,
        TokenInterface           $token,
        Closure                  $writer
    ): void;
}