<?php
declare(strict_types=1);

namespace App\Chat\Domain\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'chat_conversation')]
#[ORM\Index(columns: ['bluesky_did', 'last_turn_at'], name: 'idx_chat_conv_did_last')]
class Conversation
{
    // Migrated from `type: 'ulid'` / `Ulid`: Symfony's UlidType bypasses
    // convertToDatabaseValue() in certain ORM persist paths against
    // PostgreSQL's native UUID column, leaving the raw base32 ULID string
    // bound to a UUID parameter — Postgres then rejects it with
    // `invalid input syntax for type uuid: "01KSJ…"`. UUIDv7 is the
    // standardised time-ordered UUID variant (bit-compatible with ULID's
    // intent) and round-trips cleanly through UuidType.
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

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

    public function __construct(string $blueskyDid, ?Uuid $id = null, ?\DateTimeImmutable $now = null)
    {
        $this->id = $id ?? Uuid::v7();
        $this->blueskyDid = $blueskyDid;
        $this->createdAt = $now ?? new \DateTimeImmutable();
        $this->lastTurnAt = $this->createdAt;
        $this->turns = new ArrayCollection();
    }

    public function id(): Uuid
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
