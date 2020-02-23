<?php
declare(strict_types=1);

namespace App\Infrastructure\Amqp\Message;

use App\Api\Entity\Aggregate;
use App\Api\Entity\TokenInterface;
use App\Membership\Entity\MemberInterface;

class FetchMemberStatuses
{
    public const AGGREGATE_ID = 'aggregate_id';
    public const SCREEN_NAME = 'screen_name';
    public const BEFORE = 'before';

    /**
     * @var string
     */
    private string $screenName;

    /**
     * @var int
     */
    private int $aggregateId;

    /**
     * @var TokenInterface $token
     */
    private TokenInterface $token;

    /**
     * @var bool
     */
    private ?bool $before;

    /**
     * @param string         $screenName
     * @param int            $aggregateId
     * @param TokenInterface $token
     * @param bool|null      $before
     */
    public function __construct(
        string $screenName,
        int $aggregateId,
        TokenInterface $token,
        ?bool $before = null
    ) {
        $this->screenName = $screenName;
        $this->aggregateId = $aggregateId;
        $this->before = $before;
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function screenName(): string
    {
        return $this->screenName;
    }

    /**
     * @return int
     */
    public function aggregateId(): int
    {
        return $this->aggregateId;
    }

    /**
     * @return bool|null
     */
    public function before(): ?bool
    {
        return $this->before;
    }

    /**
     * @return TokenInterface
     */
    public function token(): TokenInterface
    {
        return $this->token;
    }

    /**
     * @param Aggregate       $aggregate
     * @param TokenInterface  $token
     * @param MemberInterface $member
     * @param bool|null       $collectPublicationsPrecedingThoseAlreadyCollected
     *
     * @return FetchMemberStatuses
     */
    public static function makeMemberIdentityCard(
        Aggregate $aggregate,
        TokenInterface $token,
        MemberInterface $member,
        ?bool $collectPublicationsPrecedingThoseAlreadyCollected
    ): FetchMemberStatuses {
        return new FetchMemberStatuses(
            $member->getTwitterUsername(),
            $aggregate->getId(),
            $token,
            $collectPublicationsPrecedingThoseAlreadyCollected
        );
    }
}

