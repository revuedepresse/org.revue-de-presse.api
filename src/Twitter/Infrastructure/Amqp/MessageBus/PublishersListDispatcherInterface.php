<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\MessageBus;

use App\Twitter\Domain\Http\Model\TokenInterface;
use App\Membership\Domain\Model\MemberInterface;

interface PublishersListDispatcherInterface
{
    public function dispatchMemberPublishersListMessage(
        MemberInterface $member,
        TokenInterface $accessToken
    ): void;
}
