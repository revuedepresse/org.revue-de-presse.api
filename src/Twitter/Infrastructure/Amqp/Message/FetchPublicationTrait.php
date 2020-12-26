<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Amqp\Message;

use App\Twitter\Infrastructure\Publication\Entity\PublishersList;
use App\Twitter\Domain\Api\Model\TokenInterface;
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
     * @param string         $screenName
     * @param int            $aggregateId
     * @param TokenInterface $token
     * @param string|null    $dateBeforeWhichStatusAreCollected
     */
    public function __construct(
        string $screenName,
        int $aggregateId,
        TokenInterface $token,
        ?string $dateBeforeWhichStatusAreCollected = null
    ) {
        $this->screenName = $screenName;
        $this->aggregateId = $aggregateId;
        $this->dateBeforeWhichStatusAreCollected = $dateBeforeWhichStatusAreCollected;
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

    public static function makeMemberIdentityCard(
        PublishersList $aggregate,
        TokenInterface $token,
        MemberInterface $member,
        ?string $dateBeforeWhichStatusAreCollected
    ): FetchPublicationInterface {
        return new self(
            $member->getTwitterUsername(),
            $aggregate->getId(),
            $token,
            $dateBeforeWhichStatusAreCollected
        );
    }
}