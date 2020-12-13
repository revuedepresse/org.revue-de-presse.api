<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\MessageBus;

use App\Twitter\Infrastructure\Api\Entity\TokenInterface;
use App\Membership\Domain\Entity\MemberInterface;

interface PublishersListDispatcherInterface
{
    public function dispatchMemberPublishersListMessage(
        MemberInterface $member,
        TokenInterface $accessToken
    ): void;
}
