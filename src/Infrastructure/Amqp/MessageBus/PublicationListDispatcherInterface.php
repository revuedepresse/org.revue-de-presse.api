<?php
declare(strict_types=1);

namespace App\Infrastructure\Amqp\MessageBus;

use App\Api\Entity\TokenInterface;
use App\Membership\Domain\Entity\MemberInterface;

interface PublicationListDispatcherInterface
{
    public function dispatchMemberPublicationListMessage(
        MemberInterface $member,
        TokenInterface $accessToken
    ): void;
}
