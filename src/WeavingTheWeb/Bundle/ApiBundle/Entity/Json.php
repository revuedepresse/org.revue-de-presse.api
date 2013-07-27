<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Json
 *
 * @ORM\Entity(repositoryClass="WeavingTheWeb\Bundle\ApiBundle\Repository\JsonRepository")
 * @ORM\Table(name="weaving_json",
 *  uniqueConstraints={
 *      @ORM\UniqueConstraint(
 *          name="jsn_hash",
 *          columns={"jsn_hash"}),
 *  },
 *  indexes={
 *      @ORM\Index(
 *          name="jsn_status",
 *          columns={"jsn_status", "jsn_type"})
 *  }
 * )
 */
class Json
{
    const TYPE_FEED = 0;

    const TYPE_REPOSITORY = 3;

    const TYPE_USER_STREAM = 2;

    /**
     * @var integer
     *
     * @ORM\Column(name="jsn_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="jsn_status", type="boolean", nullable=false)
     */
    private $status;

    /**
     * @var boolean
     *
     * @ORM\Column(name="jsn_type", type="integer", nullable=false)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="jsn_hash", type="string", length=32, nullable=false)
     */
    private $hash;

    /**
     * @var string
     *
     * @ORM\Column(name="jsn_value", type="text", nullable=false)
     */
    private $value;


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
     * @param boolean $status
     * @return Json
     */
    public function setStatus($status)
    {
        $this->status = $status;
    
        return $this;
    }

    /**
     * Get status
     *
     * @return boolean 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set type
     *
     * @param boolean $type
     * @return Json
     */
    public function setType($type)
    {
        $this->type = $type;
    
        return $this;
    }

    /**
     * Get type
     *
     * @return boolean 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set hash
     *
     * @param string $hash
     * @return Json
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
    
        return $this;
    }

    /**
     * Get hash
     *
     * @return string 
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return Json
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
}