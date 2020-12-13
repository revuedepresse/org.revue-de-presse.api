<?php
declare(strict_types=1);

namespace App\Infrastructure\Amqp\MessageBus;

use App\Infrastructure\Api\Entity\TokenInterface;
use App\Domain\Publication\PublicationListInterface;
use App\Infrastructure\Amqp\Message\FetchMemberLikes;
use App\Infrastructure\Amqp\Message\FetchMemberStatus;
use App\Infrastructure\DependencyInjection\MessageBusTrait;
use App\Infrastructure\DependencyInjection\Publication\PublicationListRepositoryTrait;
use App\Membership\Domain\Entity\MemberInterface;
use App\Domain\Publication\Exception\InvalidMemberAggregate;

class PublicationListDispatcher implements PublicationListDispatcherInterface
{
    use PublicationListRepositoryTrait;
    use MessageBusTrait;

    public function dispatchMemberPublicationListMessage(
        MemberInterface $member,
        TokenInterface $accessToken
    ): void {
        $username = $member->getTwitterUsername();

        $aggregate = $this->publicationListRepository
            ->getMemberAggregateByUsername($username);

        if (!($aggregate instanceof PublicationListInterface)) {
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
