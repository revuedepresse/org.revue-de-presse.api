<?php
declare(strict_types=1);

namespace App\Infrastructure\Amqp\MessageBus;

use App\Api\Entity\TokenInterface;
use App\Infrastructure\Amqp\Message\FetchMemberLikes;
use App\Infrastructure\Amqp\Message\FetchMemberStatus;
use App\Infrastructure\DependencyInjection\MessageBusTrait;
use App\Infrastructure\DependencyInjection\Publication\PublicationListRepositoryTrait;
use App\Membership\Entity\MemberInterface;
use App\StatusCollection\Messaging\Exception\InvalidMemberAggregate;
use WeavingTheWeb\Bundle\ApiBundle\Entity\Aggregate;

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

        if (!($aggregate instanceof Aggregate)) {
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
