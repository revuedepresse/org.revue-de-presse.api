<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Curation\Entity;

use App\Ownership\Domain\Entity\MembersListInterface;
use App\Twitter\Domain\Publication\StatusInterface;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

trait StatusTrait
{
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setStarred(bool $starred): StatusInterface
    {
        $this->starred = $starred;

        return $this;
    }

    public function setHash(string $hash = null): StatusInterface
    {
        $this->hash = $hash;

        return $this;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function setScreenName(string $screenName): StatusInterface
    {
        $this->screenName = $screenName;

        return $this;
    }

    public function getScreenName(): string
    {
        return $this->screenName;
    }

    public function setName(string $name): StatusInterface
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setText($text): StatusInterface
    {
        $this->text = $text;

        return $this;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setUserAvatar(string $userAvatar): StatusInterface
    {
        $this->userAvatar = $userAvatar;

        return $this;
    }

    public function getUserAvatar(): string
    {
        return $this->userAvatar;
    }

    public function setIdentifier(string $identifier): StatusInterface
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setStatusId(string $statusId = null): StatusInterface
    {
        $this->statusId = $statusId;

        return $this;
    }

    public function getStatusId(): string
    {
        return $this->statusId;
    }

    public function setApiDocument(string $apiDocument): StatusInterface
    {
        $this->apiDocument = $apiDocument;

        return $this;
    }

    public function getApiDocument(): string
    {
        return $this->apiDocument;
    }

    public function setCreatedAt(DateTimeInterface $createdAt): StatusInterface
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setUpdatedAt(DateTimeInterface $updatedAt = null): StatusInterface
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setIndexed(bool $indexed): StatusInterface
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
        $this->membersList = new ArrayCollection();
    }

    public function getMembersList(): Collection
    {
        return $this->membersList;
    }

    public function belongsToAList(): bool
    {
        return !$this->membersList->isEmpty();
    }

    public function removeFrom(MembersListInterface $list): StatusInterface
    {
        if (!$this->membersList->contains($list)) {
            return $this;
        }

        $this->membersList->removeElement($list);

        return $this;
    }

    public function addToMembersList(MembersListInterface $list): Collection {
        $this->membersList->add($list);

        return $this->membersList;
    }
}
