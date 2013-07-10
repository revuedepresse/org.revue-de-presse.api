<?php

namespace WTW\DashboardBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Perspective
 *
 * @ORM\Table(name="weaving_perspective")
 * @ORM\Entity(repositoryClass="WTW\DashboardBundle\Repository\PerspectiveRepository")
 */
class Perspective
{
    /**
     * @var integer
     *
     * @ORM\Column(name="per_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="per_status", type="integer", nullable=true)
     */
    protected $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="per_type", type="integer", nullable=true)
     */
    protected $type;

    /**
     * @var string
     *
     * @ORM\Column(name="per_name", type="string", nullable=true)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="per_description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var string
     *
     * @ORM\Column(name="per_value", type="text", nullable=true)
     */
    protected $value;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="per_date_creation", type="datetime", nullable=true)
     */
    protected $creationDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="per_date_update", type="datetime", nullable=true)
     */
    protected $updateDate;

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
     * Set status
     *
     * @param integer $status
     * @return Perspective
     */
    public function setStatus($status)
    {
        $this->status = $status;
    
        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set type
     *
     * @param integer $type
     * @return Perspective
     */
    public function setType($type)
    {
        $this->type = $type;
    
        return $this;
    }

    /**
     * Get type
     *
     * @return integer 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return Perspective
     */
    public function setValue($value)
    {
        $this->value = $value;
    
        return $this;
    }

    /**
     * Get value
     *
     * @return string 
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set creationDate
     *
     * @param \DateTime $creationDate
     * @return Perspective
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = $creationDate;
    
        return $this;
    }

    /**
     * Get creationDate
     *
     * @return \DateTime 
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * Set updateDate
     *
     * @param \DateTime $updateDate
     * @return Perspective
     */
    public function setUpdateDate($updateDate)
    {
        $this->updateDate = $updateDate;
    
        return $this;
    }

    /**
     * Get updateDate
     *
     * @return \DateTime 
     */
    public function getUpdateDate()
    {
        return $this->updateDate;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Perspective
     */
    public function setDescription($description)
    {
        $this->description = $description;
    
        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }
}