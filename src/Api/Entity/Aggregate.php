<?php
declare(strict_types=1);

namespace App\Api\Entity;

use App\Domain\Publication\PublicationListInterface;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Api\Repository\PublicationListRepository")
 * @ORM\Table(
 *     name="weaving_aggregate",
 *     indexes={
 *         @ORM\Index(
 *             name="name",
 *             columns={"name"}
 *         )
 *     }
 * )
 */
class Aggregate implements PublicationListInterface
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
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
     * @ORM\Column(name="total_members", type="integer", options={"default": 0})
     */
    public int $totalMembers = 0;

    /**
     * @ORM\Column(name="total_statuses", type="integer", options={"default": 0})
     */
    public int $totalStatuses = 0;

    /**
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     */
    private ?DateTimeInterface $deletedAt;

    public function markAsDeleted(): self
    {
        $this->deletedAt = new DateTime('now', new DateTimeZone('UTC'));

        return $this;
    }

    public function lock(): self
    {
        $this->locked = true;
        $this->lockedAt = new DateTime('now', new DateTimeZone('UTC'));
        $this->unlockedAt = null;

        return $this;
    }

    public function unlock(): self
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
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected DateTimeInterface $createdAt;

    /**
     * Get createdAt
     *
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function __construct(string $screenName, string $listName)
    {
        $this->name = $listName;
        $this->screenName = $screenName;
        $this->createdAt = new DateTime();
        $this->userStreams = new ArrayCollection();
        $this->locked = false;
    }

    /**
     * @ORM\ManyToMany(targetEntity="Status", mappedBy="aggregates")
     */
    protected Collection $userStreams;
}
