<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\Message;

use App\Twitter\Infrastructure\Publication\Entity\PublishersList;
use App\Twitter\Domain\Api\Model\TokenInterface;
use App\Membership\Domain\Model\MemberInterface;

interface FetchTweetInterface
{
    public const BEFORE = 'before';
    public const PUBLISHERS_LIST_ID = 'aggregate_id';
    public const SCREEN_NAME         = 'screen_name';

    public function aggregateId(): int;

    public function dateBeforeWhichStatusAreCollected(): ?string;

    public function screenName(): string;

    public function token(): TokenInterface;

    public static function identifyMember(
        PublishersList $aggregate,
        TokenInterface $token,
        MemberInterface $member,
        ?string $dateBeforeWhichStatusAreCollected
    ): FetchTweetInterface;
}