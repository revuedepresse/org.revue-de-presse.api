<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Curation\Entity;

use App\Twitter\Domain\Publication\PublishersListInterface;
use App\Twitter\Domain\Publication\TweetInterface;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @package App\Twitter\Infrastructure\Curation\Entity
 */
trait TweetTrait
{
    /**
     * @deprecated in favor of TweetTrait->id()
     */
    public function getId(): ?int
    {
        return $this->id();
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function setStarred(bool $starred): TweetInterface
    {
        $this->starred = $starred;

        return $this;
    }

    public function isStarred(): bool
    {
        return $this->starred;
    }

    public function setHash(string $hash = null): TweetInterface
    {
        $this->hash = $hash;

        return $this;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function setScreenName(string $screenName): TweetInterface
    {
        $this->screenName = $screenName;

        return $this;
    }

    /**
     * @deprecated in favor of TweetTrait->screenName()
     */
    public function getScreenName(): string
    {
        return $this->screenName();
    }

    public function screenName(): string
    {
        return $this->screenName;
    }

    public function setName(string $name): TweetInterface
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setText($text): TweetInterface
    {
        $this->text = $text;

        return $this;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setUserAvatar(string $userAvatar): TweetInterface
    {
        $this->userAvatar = $userAvatar;

        return $this;
    }

    public function getUserAvatar(): string
    {
        return $this->userAvatar;
    }

    public function setIdentifier(string $identifier): TweetInterface
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setStatusId(string $statusId = null): TweetInterface
    {
        $this->statusId = $statusId;

        return $this;
    }

    /**
     * @deprecated in favor of TweetTrait->statusId()
     */
    public function getStatusId(): string
    {
        return $this->statusId();
    }

    public function statusId(): string
    {
        return $this->statusId;
    }

    public function setApiDocument(string $apiDocument): TweetInterface
    {
        $this->apiDocument = $apiDocument;

        return $this;
    }

    public function getApiDocument(): string
    {
        return $this->apiDocument;
    }

    public function setCreatedAt(DateTimeInterface $createdAt): TweetInterface
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setUpdatedAt(DateTimeInterface $updatedAt = null): TweetInterface
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setIndexed(bool $indexed): TweetInterface
    {
        $this->indexed = $indexed;

        return $this;
    }

    public function getIndexed(): bool
    {
        return $this->indexed;
    }

    public function __construct()
    {
        $this->aggregates = new ArrayCollection();
    }

    public function getAggregates(): Collection
    {
        return $this->aggregates;
    }

    public function belongsToAList(): bool
    {
        return !$this->aggregates->isEmpty();
    }

    public function removeFrom(PublishersListInterface $aggregate): TweetInterface
    {
        if (!$this->aggregates->contains($aggregate)) {
            return $this;
        }

        $this->aggregates->removeElement($aggregate);

        return $this;
    }

    public function addToAggregates(PublishersListInterface $aggregate): Collection {
        $this->aggregates->add($aggregate);

        return $this->aggregates;
    }
}
