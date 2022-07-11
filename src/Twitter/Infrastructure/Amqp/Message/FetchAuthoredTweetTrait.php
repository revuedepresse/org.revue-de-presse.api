<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\Message;

use App\Twitter\Domain\Publication\PublishersListInterface;
use App\Twitter\Domain\Http\Model\TokenInterface;
use App\Membership\Domain\Model\MemberInterface;

trait FetchAuthoredTweetTrait
{
    private string $screenName;

    private int $listId;

    private TokenInterface $token;

    private ?bool $dateBeforeWhichStatusAreCollected;

    public function __construct(
        string         $screenName,
        int            $listId,
        TokenInterface $token,
        ?string        $dateBeforeWhichStatusAreCollected = null
    ) {
        $this->screenName = strtolower($screenName);
        $this->listId = $listId;
        $this->dateBeforeWhichStatusAreCollected = $dateBeforeWhichStatusAreCollected;
        $this->token = $token;
    }

    public function screenName(): string
    {
        return $this->screenName;
    }

    public function listId(): int
    {
        return $this->listId;
    }

    public function dateBeforeWhichStatusAreCollected(): ?string
    {
        return $this->dateBeforeWhichStatusAreCollected;
    }

    public function token(): TokenInterface
    {
        return $this->token;
    }

    public static function identifyMember(
        PublishersListInterface $aggregate,
        TokenInterface $token,
        MemberInterface $member,
        ?string $dateBeforeWhichStatusAreCollected
    ): FetchAuthoredTweetInterface {
        return new self(
            $member->twitterScreenName(),
            $aggregate->getId(),
            $token,
            $dateBeforeWhichStatusAreCollected
        );
    }
}
