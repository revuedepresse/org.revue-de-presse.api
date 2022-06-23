<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\Message;

use App\Twitter\Infrastructure\Api\Entity\Aggregate;
use App\Twitter\Infrastructure\Api\Entity\TokenInterface;
use App\Membership\Domain\Entity\MemberInterface;

trait FetchPublicationTrait
{
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
    private ?bool $dateBeforeWhichStatusAreCollected;

    /**
     * @var bool
     */
    private bool $fetchLikes;

    /**
     * @param string         $screenName
     * @param int            $aggregateId
     * @param TokenInterface $token
     * @param bool|null      $fetchLikes
     * @param string|null    $dateBeforeWhichStatusAreCollected
     */
    public function __construct(
        string $screenName,
        int $aggregateId,
        TokenInterface $token,
        bool $fetchLikes = null,
        ?string $dateBeforeWhichStatusAreCollected = null
    ) {
        $this->screenName = $screenName;
        $this->aggregateId = $aggregateId;
        $this->dateBeforeWhichStatusAreCollected = $dateBeforeWhichStatusAreCollected;
        $this->token = $token;
        $this->fetchLikes = (bool) $fetchLikes;
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
     * @return string|null
     */
    public function dateBeforeWhichStatusAreCollected(): ?string
    {
        return $this->dateBeforeWhichStatusAreCollected;
    }

    /**
     * @return TokenInterface
     */
    public function token(): TokenInterface
    {
        return $this->token;
    }

    public function shouldFetchLikes(): bool
    {
        return $this->fetchLikes;
    }

    /**
     * @param Aggregate       $aggregate
     * @param TokenInterface  $token
     * @param MemberInterface $member
     * @param bool|null       $fetchLikes
     * @param string|null     $dateBeforeWhichStatusAreCollected
     *
     * @return FetchPublicationTrait
     */
    public static function makeMemberIdentityCard(
        Aggregate $aggregate,
        TokenInterface $token,
        MemberInterface $member,
        ?string $dateBeforeWhichStatusAreCollected,
        bool $fetchLikes = false
    ): self {
        return new self(
            $member->getTwitterUsername(),
            $aggregate->getId(),
            $token,
            $fetchLikes,
            $dateBeforeWhichStatusAreCollected
        );
    }
}