<?php
declare(strict_types=1);

namespace App\Twitter\Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Api\Repository\StatusRepository")
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
 *          )
 *      }
 * )
 */
class Publication implements PublicationInterface
{
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

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getLegacyId(): int
    {
        return $this->legacyId;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getScreenName(): string
    {
        return $this->screenName;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getDocumentId(): string
    {
        return $this->documentId;
    }

    public function getDocument(): string
    {
        return $this->document;
    }

    public function getPublishedAt(): DateTimeInterface
    {
        return $this->publishedAt;
    }
}
