<?php
declare(strict_types=1);

namespace App\Subscription\Infrastructure\Entity;

use App\Membership\Domain\Model\MemberInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Ramsey\Uuid\UuidInterface;
use const JSON_THROW_ON_ERROR;

class ListSubscription
{
    private UuidInterface $id;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    private MemberInterface $member;

    public function member(): MemberInterface
    {
        return $this->member;
    }

    public string $listId;

    public function listId(): string
    {
        return $this->listId;
    }

    private string $listName;

    public function listName(): string
    {
        return $this->listName;
    }

    private string $document;

    public function setDocument(string $document): self
    {
        $this->document = $document;

        return $this;
    }

    public function setName(string $name): self
    {
        $this->listName = $name;

        return $this;
    }

    private Collection $aggregates;

    public function __construct(MemberInterface $member, array $document)
    {
        $this->member = $member;
        $this->document = json_encode($document, JSON_THROW_ON_ERROR);
        $this->listName = $document['name'];
        $this->listId = $document['id'];
        $this->aggregates = new ArrayCollection();
    }
}
