<?php
declare(strict_types=1);

namespace App\Chat\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'chat_turn')]
#[ORM\Index(columns: ['conversation_id', 'created_at'], name: 'idx_chat_turn_conv_created')]
class ConversationTurn
{
    public const ROLE_USER = 'user';
    public const ROLE_ASSISTANT = 'assistant';

    #[ORM\Id]
    #[ORM\Column(type: 'ulid', unique: true)]
    private Ulid $id;

    #[ORM\ManyToOne(targetEntity: Conversation::class, inversedBy: 'turns')]
    #[ORM\JoinColumn(name: 'conversation_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Conversation $conversation;

    #[ORM\Column(type: 'string', length: 16)]
    private string $role;

    #[ORM\Column(type: 'text')]
    private string $content;

    /** @var list<string>|null */
    #[ORM\Column(name: 'cited_publication_ids', type: 'json', nullable: true)]
    private ?array $citedPublicationIds;

    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    private ?string $provider;

    #[ORM\Column(name: 'prompt_tokens', type: 'integer', nullable: true)]
    private ?int $promptTokens;

    #[ORM\Column(name: 'completion_tokens', type: 'integer', nullable: true)]
    private ?int $completionTokens;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $truncated;

    #[ORM\Column(name: 'created_at', type: 'datetimetz_immutable')]
    private \DateTimeImmutable $createdAt;

    /**
     * @param list<string>|null $citedPublicationIds
     */
    public function __construct(
        Conversation $conversation,
        string $role,
        string $content,
        ?array $citedPublicationIds = null,
        ?string $provider = null,
        ?int $promptTokens = null,
        ?int $completionTokens = null,
        bool $truncated = false,
        ?Ulid $id = null,
        ?\DateTimeImmutable $createdAt = null,
    ) {
        if ($role !== self::ROLE_USER && $role !== self::ROLE_ASSISTANT) {
            throw new \InvalidArgumentException(\sprintf(
                'role must be "%s" or "%s", got "%s"',
                self::ROLE_USER,
                self::ROLE_ASSISTANT,
                $role,
            ));
        }

        $this->id = $id ?? new Ulid();
        $this->conversation = $conversation;
        $this->role = $role;
        $this->content = $content;
        $this->citedPublicationIds = $citedPublicationIds;
        $this->provider = $provider;
        $this->promptTokens = $promptTokens;
        $this->completionTokens = $completionTokens;
        $this->truncated = $truncated;
        $this->createdAt = $createdAt ?? new \DateTimeImmutable();
    }

    public function id(): Ulid
    {
        return $this->id;
    }

    public function conversation(): Conversation
    {
        return $this->conversation;
    }

    public function role(): string
    {
        return $this->role;
    }

    public function content(): string
    {
        return $this->content;
    }

    /** @return list<string>|null */
    public function citedPublicationIds(): ?array
    {
        return $this->citedPublicationIds;
    }

    public function provider(): ?string
    {
        return $this->provider;
    }

    public function promptTokens(): ?int
    {
        return $this->promptTokens;
    }

    public function completionTokens(): ?int
    {
        return $this->completionTokens;
    }

    public function truncated(): bool
    {
        return $this->truncated;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function markTruncated(): void
    {
        $this->truncated = true;
    }
}
