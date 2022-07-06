<?php

declare(strict_types=1);

namespace App\Twitter\Infrastructure\Publication\Entity;

use App\Twitter\Domain\Publication\PublishersListInterface;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Ramsey\Uuid\Rfc4122\UuidV5;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Twitter\Infrastructure\Http\Repository\PublishersListRepository")
 * @ORM\Table(
 *     name="publishers_list",
 *     indexes={
 *         @ORM\Index(
 *             name="name",
 *             columns={"name", "screen_name"}
 *         )
 *     }
 * )
 */
class PublishersList implements PublishersListInterface
{
    private const NAMESPACE = '14549ef6-01b8-4925-be2c-636cd9580b5d';

    /**
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected string $name;

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="screen_name", type="string", length=255, nullable=true)
     */
    public ?string $screenName;

    /**
     * @var bool
     * @ORM\Column(name="locked", type="boolean")
     */
    public bool $locked;

    /**
     * @ORM\Column(name="locked_at", type="datetime", nullable=true)
     */
    public ?DateTimeInterface $lockedAt;

    /**
     * @ORM\Column(name="unlocked_at", type="datetime", nullable=true)
     */
    public ?DateTimeInterface $unlockedAt;

    /**
     * @ORM\Column(name="list_id", type="string", nullable=true)
     */
    public ?string $listId;

    /**
     * @ORM\Column(name="public_id", type="uuid")
     */
    public UuidInterface $publicId;

    public function publicId(): UuidInterface
    {
        return $this->publicId;
    }

    /**
     * @ORM\Column(name="total_members", type="integer", options={"default": 0})
     */
    public int $totalMembers = 0;

    public function setTotalMembers(int $totalMembers): PublishersListInterface
    {
        $this->totalMembers = $totalMembers;

        return $this;
    }

    /**
     * @ORM\Column(name="total_statuses", type="integer", options={"default": 0})
     */
    private int $totalStatuses = 0;

    public function totalStatus(): int
    {
        return $this->totalStatuses;
    }

    public function setTotalStatus(int $totalStatus): PublishersListInterface
    {
        $this->totalStatuses = $totalStatus;

        return $this;
    }

    /**
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     */
    private ?DateTimeInterface $deletedAt;

    public function markAsDeleted(): PublishersListInterface
    {
        $this->deletedAt = new DateTime('now', new DateTimeZone('UTC'));

        return $this;
    }

    public function lock(): PublishersListInterface
    {
        $this->locked = true;
        $this->lockedAt = new DateTime('now', new DateTimeZone('UTC'));
        $this->unlockedAt = null;

        return $this;
    }

    public function unlock(): PublishersListInterface
    {
        $this->locked = false;
        $this->lockedAt = null;
        $this->unlockedAt = new DateTime('now', new DateTimeZone('UTC'));

        return $this;
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }

    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected DateTimeInterface $createdAt;

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function __construct(string $screenName, string $listName)
    {
        if ($listName === null) {
            throw new InvalidArgumentException(
                'An aggregate requires a valid list name.'
            );
        }

        $this->publicId = UuidV5::uuid5(self::NAMESPACE, $listName);
        $this->name = $listName;
        $this->screenName = $screenName;
        $this->createdAt = new DateTime();
        $this->userStreams = new ArrayCollection();
        $this->locked = false;
    }

    /**
     * @ORM\ManyToMany(targetEntity="App\Twitter\Infrastructure\Http\Entity\Tweet", mappedBy="aggregates")
     */
    protected Collection $userStreams;
}
