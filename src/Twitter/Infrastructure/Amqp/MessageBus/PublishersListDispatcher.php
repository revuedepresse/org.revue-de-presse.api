<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\MessageBus;

use App\Twitter\Domain\Http\Model\TokenInterface;
use App\Twitter\Domain\Publication\PublishersListInterface;
use App\Twitter\Infrastructure\Amqp\Message\FetchTweet;
use App\Twitter\Infrastructure\DependencyInjection\MessageBusTrait;
use App\Twitter\Infrastructure\DependencyInjection\Publication\PublishersListRepositoryTrait;
use App\Membership\Domain\Model\MemberInterface;
use App\Twitter\Domain\Publication\Exception\InvalidMemberAggregate;

class PublishersListDispatcher implements PublishersListDispatcherInterface
{
    use PublishersListRepositoryTrait;
    use MessageBusTrait;

    public function dispatchMemberPublishersListMessage(
        MemberInterface $member,
        TokenInterface $accessToken
    ): void {
        $username = $member->twitterScreenName();

        $aggregate = $this->publishersListRepository
            ->getMemberAggregateByUsername($username);

        if (!($aggregate instanceof PublishersListInterface)) {
            InvalidMemberAggregate::guardAgainstInvalidUsername($username);
        }

        $fetchMemberStatus = new FetchTweet(
            $username,
            $aggregate->getId(),
            $accessToken
        );
        $this->dispatcher->dispatch($fetchMemberStatus);
    }
}
