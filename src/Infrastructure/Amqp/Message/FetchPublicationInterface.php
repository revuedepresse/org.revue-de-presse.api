<?php
declare(strict_types=1);

namespace App\Infrastructure\Amqp\Message;

use App\Api\Entity\Aggregate;
use App\Api\Entity\TokenInterface;
use App\Membership\Entity\MemberInterface;

interface FetchPublicationInterface
{
    public const AGGREGATE_ID = 'aggregate_id';
    public const SCREEN_NAME = 'screen_name';
    public const BEFORE = 'before';

    public function aggregateId(): int;

    public function dateBeforeWhichStatusAreCollected(): ?string;

    public function screenName(): string;

    public function shouldFetchLikes(): bool;

    public function token(): TokenInterface;

    public static function makeMemberIdentityCard(
        Aggregate $aggregate,
        TokenInterface $token,
        MemberInterface $member,
        bool $fetchLikes,
        ?string $dateBeforeWhichStatusAreCollected
    ): self;
}