<?php
declare(strict_types=1);

namespace App\Chat\Domain\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'chat_conversation')]
#[ORM\Index(columns: ['bluesky_did', 'last_turn_at'], name: 'idx_chat_conv_did_last')]
class Conversation
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid', unique: true)]
    private Ulid $id;

    #[ORM\Column(name: 'bluesky_did', type: 'string', length: 255)]
    private string $blueskyDid;

    #[ORM\Column(name: 'created_at', type: 'datetimetz_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'last_turn_at', type: 'datetimetz_immutable')]
    private \DateTimeImmutable $lastTurnAt;

    /** @var Collection<int, ConversationTurn> */
    #[ORM\OneToMany(targetEntity: ConversationTurn::class, mappedBy: 'conversation', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $turns;

    public function __construct(string $blueskyDid, ?Ulid $id = null, ?\DateTimeImmutable $now = null)
    {
        $this->id = $id ?? new Ulid();
        $this->blueskyDid = $blueskyDid;
        $this->createdAt = $now ?? new \DateTimeImmutable();
        $this->lastTurnAt = $this->createdAt;
        $this->turns = new ArrayCollection();
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function blueskyDid(): string
    {
        return $this->blueskyDid;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function lastTurnAt(): \DateTimeImmutable
    {
        return $this->lastTurnAt;
    }

    /** @return Collection<int, ConversationTurn> */
    public function turns(): Collection
    {
        return $this->turns;
    }

    public function touch(\DateTimeImmutable $at): void
    {
        $this->lastTurnAt = $at;
    }
}
