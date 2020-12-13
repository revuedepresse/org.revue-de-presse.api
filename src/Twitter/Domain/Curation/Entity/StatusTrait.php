<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Curation\Entity;

use App\Twitter\Domain\Publication\PublishersListInterface;
use App\Twitter\Domain\Publication\StatusInterface;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @package App\Twitter\Domain\Curation\Entity
 */
trait StatusTrait
{
    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param bool $starred
     *
     * @return $this
     */
    public function setStarred(bool $starred): StatusInterface
    {
        $this->starred = $starred;

        return $this;
    }

    /**
     * @return bool
     */
    public function isStarred(): bool
    {
        return $this->starred;
    }

    /**
     * @param string $hash
     *
     * @return $this
     */
    public function setHash(string $hash = null): StatusInterface
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @param string $screenName
     *
     * @return $this
     */
    public function setScreenName(string $screenName): StatusInterface
    {
        $this->screenName = $screenName;

        return $this;
    }

    /**
     * @return string
     */
    public function getScreenName(): string
    {
        return $this->screenName;
    }

    /**
     * @param string $name
     *
     * @return self
     */
    public function setName(string $name): StatusInterface
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $text
     *
     * @return $this
     */
    public function setText($text): StatusInterface
    {
        $this->text = $text;

        return $this;
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @param string $userAvatar
     *
     * @return $this|StatusInterface
     */
    public function setUserAvatar(string $userAvatar): StatusInterface
    {
        $this->userAvatar = $userAvatar;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserAvatar(): string
    {
        return $this->userAvatar;
    }

    /**
     * @param string $identifier
     *
     * @return $this|StatusInterface
     */
    public function setIdentifier(string $identifier): StatusInterface
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @param string $statusId
     *
     * @return $this
     */
    public function setStatusId(string $statusId = null): StatusInterface
    {
        $this->statusId = $statusId;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatusId(): string
    {
        return $this->statusId;
    }

    /**
     * @param string $apiDocument
     *
     * @return $this
     */
    public function setApiDocument(string $apiDocument): StatusInterface
    {
        $this->apiDocument = $apiDocument;

        return $this;
    }

    /**
     * @return mixed|string|null
     */
    public function getApiDocument(): string
    {
        return $this->apiDocument;
    }

    /**
     * @param DateTimeInterface $createdAt
     *
     * @return $this
     */
    public function setCreatedAt(DateTimeInterface $createdAt): StatusInterface
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return DateTimeInterface
     */
    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @param $updatedAt
     * @return $this
     */
    public function setUpdatedAt(DateTimeInterface $updatedAt = null): StatusInterface
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return DateTimeInterface
     */
    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * @param $indexed
     * @return $this
     */
    public function setIndexed(bool $indexed): StatusInterface
    {
        $this->indexed = $indexed;

        return $this;
    }

    /**
     * @return bool
     */
    public function getIndexed(): bool
    {
        return $this->indexed;
    }

    public function __construct()
    {
        $this->aggregates = new ArrayCollection();
    }

    /**
     * @return ArrayCollection
     */
    public function getAggregates(): Collection
    {
        return $this->aggregates;
    }

    /**
     * @return bool
     */
    public function belongsToAList(): bool
    {
        return !$this->aggregates->isEmpty();
    }

    /**
     * @param PublishersListInterface $aggregate
     *
     * @return $this
     */
    public function removeFrom(PublishersListInterface $aggregate): StatusInterface
    {
        if (!$this->aggregates->contains($aggregate)) {
            return $this;
        }

        $this->aggregates->removeElement($aggregate);

        return $this;
    }

    /**
     * @param PublishersListInterface $aggregate
     *
     * @return Collection
     */
    public function addToAggregates(PublishersListInterface $aggregate): Collection {
        $this->aggregates->add($aggregate);

        return $this->aggregates;
    }

}
