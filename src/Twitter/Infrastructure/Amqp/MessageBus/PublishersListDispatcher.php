<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\MessageBus;

use App\Twitter\Infrastructure\Api\Entity\TokenInterface;
use App\Twitter\Domain\Publication\PublishersListInterface;
use App\Twitter\Infrastructure\Amqp\Message\FetchMemberLikes;
use App\Twitter\Infrastructure\Amqp\Message\FetchMemberStatus;
use App\Twitter\Infrastructure\DependencyInjection\MessageBusTrait;
use App\Twitter\Infrastructure\DependencyInjection\Publication\PublishersListRepositoryTrait;
use App\Membership\Domain\Entity\MemberInterface;
use App\Twitter\Domain\Publication\Exception\InvalidMemberAggregate;

class PublishersListDispatcher implements PublishersListDispatcherInterface
{
    use PublishersListRepositoryTrait;
    use MessageBusTrait;

    public function dispatchMemberPublishersListMessage(
        MemberInterface $member,
        TokenInterface $accessToken
    ): void {
        $username = $member->getTwitterUsername();

        $aggregate = $this->publishersListRepository
            ->getMemberAggregateByUsername($username);

        if (!($aggregate instanceof PublishersListInterface)) {
            InvalidMemberAggregate::guardAgainstInvalidUsername($username);
        }

        $fetchMemberStatus = new FetchMemberStatus(
            $username,
            $aggregate->getId(),
            $accessToken
        );
        $this->dispatcher->dispatch($fetchMemberStatus);

        $fetchLikedStatusMessage = FetchMemberLikes::from($fetchMemberStatus);
        $this->dispatcher->dispatch($fetchLikedStatusMessage);
    }
}
