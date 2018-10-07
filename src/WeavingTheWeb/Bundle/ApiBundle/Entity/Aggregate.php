<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="WeavingTheWeb\Bundle\ApiBundle\Repository\AggregateRepository")
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
class Aggregate
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
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="screen_name", type="string", length=255, nullable=true)
     */
    public $screenName;

    /**
     * @var bool
     * @ORM\Column(name="locked", type="boolean")
     */
    public $locked;

    /**
     * @var bool
     * @ORM\Column(name="locked_at", type="datetime", nullable=true)
     */
    public $lockedAt;

    /**
     * @var bool
     * @ORM\Column(name="unlocked_at", type="datetime", nullable=true)
     */
    public $unlockedAt;

    public function lock()
    {
        $this->locked = true;
        $this->lockedAt = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->unlockedAt = null;

        return $this;
    }

    public function unlock()
    {
        $this->locked = false;
        $this->lockedAt = null;
        $this->unlockedAt = new \DateTime('now', new \DateTimeZone('UTC'));

        return $this;
    }

    public function isLocked()
    {
        return $this->locked;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param $screenName
     * @param $listName
     */
    public function __construct(string $screenName, string $listName)
    {
        $this->name = $listName;
        $this->screenName = $screenName;
        $this->createdAt = new \DateTime();
        $this->userStreams = new ArrayCollection();
        $this->locked = false;
    }

    /**
     * @ORM\ManyToMany(targetEntity="Status", mappedBy="aggregates")
     */
    protected $userStreams;
}
