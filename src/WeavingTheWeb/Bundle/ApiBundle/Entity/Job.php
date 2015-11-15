<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use WeavingTheWeb\Bundle\DashboardBundle\Entity\Awareness\UserAware,
    WeavingTheWeb\Bundle\DashboardBundle\Entity\Awareness\UserAwareInterface;

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
class Job implements JobInterface, UserAwareInterface
{
    use UserAware;

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
     * @ORM\Column(name="job_value", type="text")
     */
    private $value;

    /**
     * @var string
     *
     * @ORM\Column(name="job_output", type="text", nullable=true)
     */
    private $output;

    /**
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @param string $output
     */
    public function setOutput($output)
    {
        $this->output = $output;
    }

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

    public function isIdle()
    {
        return $this->status === self::STATUS_IDLE;
    }

    public function hasFailed()
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isStarted()
    {
        return $this->status === self::STATUS_STARTED;
    }

    public function hasFinished()
    {
        return $this->status === self::STATUS_FINISHED;
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
