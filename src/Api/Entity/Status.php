<?php

namespace App\Api\Entity;

use App\Status\Entity\StatusTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="WeavingTheWeb\Bundle\ApiBundle\Repository\StatusRepository")
 * @ORM\Table(
 *      name="weaving_status",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="unique_hash", columns={"ust_hash"}),
 *      },
 *      options={"collate":"utf8mb4_general_ci", "charset":"utf8mb4"},
 *      indexes={
 *          @ORM\Index(name="hash", columns={"ust_hash"}),
 *          @ORM\Index(name="screen_name", columns={"ust_full_name"}),
 *          @ORM\Index(name="status_id", columns={"ust_status_id"}),
 *          @ORM\Index(name="indexed", columns={"ust_indexed"}),
 *          @ORM\Index(name="ust_created_at", columns={"ust_created_at"})
 *      }
 * )
 */
class Status implements StatusInterface
{
    use StatusTrait;

    /**
     * @var integer
     *
     * @ORM\Column(name="ust_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="ust_hash", type="string", length=40, nullable=true)
     */
    protected $hash;

    /**
     * @ORM\Column(name="ust_full_name", type="string", length=32)
     */
    protected $screenName;

    /**
     * @ORM\Column(name="ust_name", type="text")
     */
    protected $name;

    /**
     * @ORM\Column(name="ust_text", type="text")
     */
    protected $text;

    /**
     * @ORM\Column(name="ust_avatar", type="string", length=255)
     */
    protected $userAvatar;

    /**
     * @ORM\Column(name="ust_access_token", type="string", length=255)
     */
    protected $identifier;

    /**
     * @ORM\Column(name="ust_status_id", type="string", length=255, nullable=true)
     */
    protected $statusId;

    /**
     * @ORM\Column(name="ust_api_document", type="text", nullable=true)
     */
    protected $apiDocument;

    /**
     * @ORM\Column(name="ust_starred", type="boolean", options={"default": false})
     */
    protected $starred = false;

    /**
     * @param $starred
     * @return $this
     */
    public function setStarred($starred)
    {
        $this->starred = $starred;

        return $this;
    }

    /**
     * @return mixed
     */
    public function isStarred()
    {
        return $this->starred;
    }

    /**
     * @ORM\Column(name="ust_indexed", type="boolean", options={"default": false})
     */
    protected $indexed;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="ust_created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="ust_updated_at", type="datetime", nullable=true)
     */
    protected $updatedAt;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $hash
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Set screeName
     *
     * @param  string     $screenName
     * @return Status
     */
    public function setScreenName($screenName)
    {
        $this->screenName = $screenName;

        return $this;
    }

    /**
     * Get screeName
     *
     * @return string
     */
    public function getScreenName()
    {
        return $this->screenName;
    }

    /**
     * Set name
     *
     * @param  string     $name
     * @return Status
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set text
     *
     * @param  string     $text
     * @return Status
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get text
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set userAvatar
     *
     * @param  string     $userAvatar
     * @return Status
     */
    public function setUserAvatar($userAvatar)
    {
        $this->userAvatar = $userAvatar;

        return $this;
    }

    /**
     * Get userAvatar
     *
     * @return string
     */
    public function getUserAvatar()
    {
        return $this->userAvatar;
    }

    /**
     * Set identifier
     *
     * @param  string     $identifier
     * @return Status
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * Get identifier
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function setStatusId($statusId)
    {
        $this->statusId = $statusId;
    }

    public function getStatusId()
    {
        return $this->statusId;
    }

    public function setApiDocument($apiDocument)
    {
        $this->apiDocument = $apiDocument;
    }

    public function getApiDocument()
    {
        return $this->apiDocument;
    }

    /**
     * Set createdAt
     *
     * @param  \DateTime  $createdAt
     * @return Status
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

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
     * Set updatedAt
     *
     * @param  \DateTime  $updatedAt
     * @return Status
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set indexed
     *
     * @param boolean $indexed
     * @return Status
     */
    public function setIndexed($indexed)
    {
        $this->indexed = $indexed;
    
        return $this;
    }

    /**
     * Get indexed
     *
     * @return boolean 
     */
    public function getIndexed()
    {
        return $this->indexed;
    }

    /**
     * @ORM\ManyToMany(
     *     targetEntity="Aggregate",
     *     inversedBy="userStreams",
     *     cascade={"persist"}
     * )
     * @ORM\JoinTable(name="weaving_status_aggregate",
     *      joinColumns={
     *          @ORM\JoinColumn(
     *              name="status_id",
     *              referencedColumnName="ust_id"
     *          )
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(
     *              name="aggregate_id",
     *              referencedColumnName="id"
     *          )
     *      }
     * )
     */
    protected $aggregates;

    public function __construct()
    {
        $this->aggregates = new ArrayCollection();
    }

    /**
     * @param Aggregate $aggregate
     * @return StatusInterface
     */
    public function removeFrom(Aggregate $aggregate): StatusInterface
    {
        if ($this->aggregates->contains($aggregate)) {
            $this->aggregates->removeElement($aggregate);
        }

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getAggregates()
    {
        return $this->aggregates;
    }

    /**
     * @ORM\OneToMany(targetEntity="App\Popularity\Entity\StatusPopularity", mappedBy="status")
     */
    private $popularity;

    /**
     * @param Aggregate $aggregate
     *
     * @return ArrayCollection|mixed
     */
    public function addToAggregates(Aggregate $aggregate) {
        $this->aggregates->add($aggregate);

        return $this->aggregates;
    }
}
