<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Publication\Entity;

use App\Twitter\Domain\Publication\Repository\PublicationInterface;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Twitter\Infrastructure\Publication\Repository\PublicationRepository")
 * @ORM\Table(
 *      name="publication",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="unique_hash", columns={"hash"}),
 *      },
 *      options={"collate":"utf8mb4_general_ci", "charset":"utf8mb4"},
 *      indexes={
 *          @ORM\Index(
 *              name="idx_publication",
 *              columns={"hash", "screen_name", "document_id", "published_at"}
 *          ),
 *          @ORM\Index(
 *              name="idx_legacy",
 *              columns={"legacy_id", "document_id", "published_at"}
 *          )
 *      }
 * )
 */
class Publication implements PublicationInterface
{
    /**
     * @param array $item
     *
     * @return static
     */
    public static function fromArray(array $item): self
    {
        return new self(
            $item['legacy_id'],
            $item['hash'],
            $item['avatar_url'],
            $item['screen_name'],
            $item['text'],
            $item['document_id'],
            $item['document'],
            $item['published_at'],
        );
    }

    /**
     * @ORM\Column(name="legacy_id", type="integer", nullable=true)
     */
    protected ?int $legacyId = null;

    /**
     * @ORM\Id
     * @ORM\Column(type="uuid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
     */
    protected UuidInterface $id;

    /**
     * @ORM\Column(name="hash", type="string", length=64)
     */
    protected string $hash;

    /**
     * @ORM\Column(name="avatar_url", type="string", length=255)
     */
    protected string $avatarUrl;

    /**
     * @ORM\Column(name="screen_name", type="string", length=32)
     */
    protected string $screenName;

    /**
     * @ORM\Column(name="text", type="text")
     */
    protected string $text;

    /**
     * @ORM\Column(name="document_id", type="string", length=255)
     */
    protected string $documentId;

    /**
     * @ORM\Column(name="document", type="text")
     */
    protected string $document;

    /**
     * @ORM\Column(name="published_at", type="datetime")
     */
    protected DateTimeInterface $publishedAt;

    public function __construct(
        int $legacyId,
        string $hash,
        string $avatarUrl,
        string $screenName,
        string $text,
        string $documentId,
        string $document,
        \DateTimeInterface $publishedAt
    ) {
        $this->legacyId    = $legacyId;
        $this->hash        = $hash;
        $this->avatarUrl   = $avatarUrl;
        $this->screenName  = $screenName;
        $this->text        = $text;
        $this->documentId  = $documentId;
        $this->document    = $document;
        $this->publishedAt = $publishedAt;

        $this->collectPublicationEvents = new ArrayCollection();
    }

    public function getAvatarUrl(): string
    {
        return $this->avatarUrl;
    }

    public function getDocument(): string
    {
        return $this->document;
    }

    public function getDocumentId(): string
    {
        return $this->documentId;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getLegacyId(): int
    {
        return $this->legacyId;
    }

    public function getPublishedAt(): DateTimeInterface
    {
        return $this->publishedAt;
    }

    public function getScreenName(): string
    {
        return $this->screenName;
    }

    public function getText(): string
    {
        return $this->text;
    }
}
