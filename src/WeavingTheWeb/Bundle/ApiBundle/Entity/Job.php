<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 * @ORM\Entity(repositoryClass="WeavingTheWeb\Bundle\ApiBundle\Repository\JobRepository")
 * @ORM\Table(name="weaving_job",
 *  indexes={
 *      @ORM\Index(
 *          name="job_status",
 *          columns={"job_status", "job_type"})
 *  }
 * )
 */
class Job implements JobInterface
{
    /**
     * @var integer
     *
     * @ORM\Column(name="job_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="job_status", type="integer", options={"default": 10})
     */
    private $status = self::STATUS_IDLE;

    /**
     * @var integer
     *
     * @ORM\Column(name="job_type", type="integer", options={"default": 0})
     */
    private $type = self::TYPE_COMMAND;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="job_created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="job_updated_at", type="datetime", nullable=true)
     */
    protected $updatedAt;


    /**
     * @var string
     *
     * @ORM\Column(name="jsn_value", type="text")
     */
    private $value;

    public function __construct($status = self::STATUS_IDLE, $type = self::TYPE_COMMAND) {
        $this->status = $status;
        $this->type = $type;
        $this->createdAt = new \DateTime();
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }


    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }
}
