<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Publication;

use App\Twitter\Domain\Publication\PublishersListInterface;
use DateTimeInterface;
use Doctrine\Common\Collections\Collection;

/**
 * @package App\Twitter\Infrastructure\Api\Entity
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
    public function setHash(string $hash): StatusInterface;

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
    public function setName(string $name): StatusInterface;

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
    public function setText(string $text): StatusInterface;

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
    public function setUserAvatar(string $userAvatar): StatusInterface;

    /**
     * @return string
     */
    public function getUserAvatar(): string;

    /**
     * @param $identifier
     * @return $this
     */
    public function setIdentifier(string $identifier): StatusInterface;

    /**
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * @param string $statusId
     *
     * @return $this
     */
    public function setStatusId(string $statusId): StatusInterface;

    /**
     * @return string
     */
    public function getStatusId(): string;

    /**
     * @param string $apiDocument
     *
     * @return $this
     */
    public function setApiDocument(string $apiDocument): StatusInterface;

    /**
     * @return mixed
     */
    public function getApiDocument(): string;

    /**
     * @param DateTimeInterface $createdAt
     *
     * @return $this
     */
    public function setCreatedAt(DateTimeInterface $createdAt): StatusInterface;

    /**
     * @return DateTimeInterface
     */
    public function getCreatedAt(): DateTimeInterface;

    /**
     * @param DateTimeInterface $updatedAt
     *
     * @return mixed
     */
    public function setUpdatedAt(DateTimeInterface $updatedAt): StatusInterface;

    /**
     * @return DateTimeInterface
     */
    public function getUpdatedAt(): ?DateTimeInterface;

    /**
     * @param bool $indexed
     * @return $this
     */
    public function setIndexed(bool $indexed): StatusInterface;

    /**
     * @return bool
     */
    public function getIndexed(): bool;

    /**
     * @return Collection
     */
    public function getAggregates(): Collection;

    /**
     * @param PublishersListInterface $aggregate
     * @return self
     */
    public function removeFrom(PublishersListInterface $aggregate): StatusInterface;

    /**
     * @param PublishersListInterface $aggregate
     * @return mixed
     */
    public function addToAggregates(PublishersListInterface $aggregate): Collection;

    /**
     * @return bool
     */
    public function belongsToAList(): bool;

    /**
     * @return $this
     */
    public function markAsPublished(): StatusInterface;
}
