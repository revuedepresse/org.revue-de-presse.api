<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\Message;

use App\Twitter\Infrastructure\Api\Entity\Aggregate;
use App\Twitter\Infrastructure\Api\Entity\TokenInterface;
use App\Membership\Domain\Entity\MemberInterface;

interface FetchPublicationInterface
{
    public const BEFORE = 'before';
    public const publishers_list_ID = 'aggregate_id';
    public const SCREEN_NAME         = 'screen_name';

    public function aggregateId(): int;

    public function dateBeforeWhichStatusAreCollected(): ?string;

    public function screenName(): string;

    public function shouldFetchLikes(): bool;

    public function token(): TokenInterface;

    public static function makeMemberIdentityCard(
        Aggregate $aggregate,
        TokenInterface $token,
        MemberInterface $member,
        ?string $dateBeforeWhichStatusAreCollected,
        bool $fetchLikes = false
    ): self;
}