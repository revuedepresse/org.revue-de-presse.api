<?php
declare(strict_types=1);

namespace App\Search\Infrastructure\Ruleset;

use App\Search\Domain\Ruleset\SearchQueryRulesetProcessorInterface;
use App\Twitter\Domain\Curation\CurationRulesetInterface;
use App\Twitter\Domain\Http\Model\TokenInterface;
use App\Twitter\Infrastructure\Amqp\Message\FetchSearchQueryMatchingTweet;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\MessageBusInterface;

class SearchQueryRulesetProcessor implements SearchQueryRulesetProcessorInterface
{
    private MessageBusInterface $dispatcher;

    public function __construct(MessageBus $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function processSearchQuery(
        CurationRulesetInterface $ruleset,
        TokenInterface $token
    ) {
        $fetchTweetMatchingSearchQueryAmqpMessage = FetchSearchQueryMatchingTweet::matchWithSearchQuery(
            $ruleset->searchQuery(),
            $token,
            $ruleset->tweetCreationDateFilter()
        );

        $this->dispatcher->dispatch($fetchTweetMatchingSearchQueryAmqpMessage);
    }
}
