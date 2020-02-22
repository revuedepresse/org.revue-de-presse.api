<?php
declare(strict_types=1);

namespace App\Api\Entity;

use DateTimeInterface;
use Doctrine\Common\Collections\Collection;

/**
 * @package App\Api\Entity
 */
interface StatusInterface
{
    /**
     * @return int
     */
    public function getId(): ?int;

    /**
     * @param string $hash
     *
     * @return $this
     */
    public function setHash(string $hash): self;

    /**
     * @return string
     */
    public function getHash(): string;

    /**
     * @param string $screenName
     *
     * @return mixed
     */
    public function setScreenName(string $screenName);

    /**
     * @return $this
     */
    public function getScreenName(): string;

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name): self;

    /**
     * Get name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * @param $text
     * @return $this
     */
    public function setText(string $text): self;

    /**
     * Get text
     *
     * @return string
     */
    public function getText(): string;

    /**
     * @param $userAvatar
     * @return $this
     */
    public function setUserAvatar(string $userAvatar): self;

    /**
     * @return string
     */
    public function getUserAvatar(): string;

    /**
     * @param $identifier
     * @return $this
     */
    public function setIdentifier(string $identifier): self;

    /**
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * @param string $statusId
     *
     * @return $this
     */
    public function setStatusId(string $statusId): self;

    /**
     * @return string
     */
    public function getStatusId(): string;

    /**
     * @param string $apiDocument
     *
     * @return $this
     */
    public function setApiDocument(string $apiDocument): self;

    /**
     * @return mixed
     */
    public function getApiDocument(): string;

    /**
     * @param DateTimeInterface $createdAt
     *
     * @return $this
     */
    public function setCreatedAt(DateTimeInterface $createdAt): self;

    /**
     * @return DateTimeInterface
     */
    public function getCreatedAt(): DateTimeInterface;

    /**
     * @param DateTimeInterface $updatedAt
     *
     * @return mixed
     */
    public function setUpdatedAt(DateTimeInterface $updatedAt): self;

    /**
     * @return DateTimeInterface
     */
    public function getUpdatedAt(): ?DateTimeInterface;

    /**
     * @param bool $indexed
     * @return $this
     */
    public function setIndexed(bool $indexed): self;

    /**
     * @return bool
     */
    public function getIndexed(): bool;

    /**
     * @return Collection
     */
    public function getAggregates(): Collection;

    /**
     * @param Aggregate $aggregate
     * @return self
     */
    public function removeFrom(Aggregate $aggregate): self;

    /**
     * @param Aggregate $aggregate
     * @return mixed
     */
    public function addToAggregates(Aggregate $aggregate): Collection;

    /**
     * @return $this
     */
    public function markAsPublished(): self;
}
