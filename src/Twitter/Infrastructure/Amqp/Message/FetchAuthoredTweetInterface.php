<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\Message;

use App\Membership\Domain\Model\MemberInterface;
use App\Twitter\Domain\Http\Model\TokenInterface;
use App\Twitter\Infrastructure\Publication\Entity\PublishersList;

interface FetchAuthoredTweetInterface extends FetchTweetInterface
{
    public const BEFORE = 'before';
    public const TWITTER_LIST_ID = 'twitter_list_id';
    public const SCREEN_NAME = 'screen_name';

    public function listId(): int;

    public function dateBeforeWhichStatusAreCollected(): ?string;

    public function screenName(): string;

    public function token(): TokenInterface;

    public static function identifyMember(
        PublishersList  $aggregate,
        TokenInterface  $token,
        MemberInterface $member,
        ?string         $dateBeforeWhichStatusAreCollected
    ): self;
}
