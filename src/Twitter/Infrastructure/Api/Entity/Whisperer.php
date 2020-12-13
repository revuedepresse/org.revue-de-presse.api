<?php

namespace App\Twitter\Infrastructure\Api\Entity;

use Doctrine\ORM\Mapping as ORM;
use MyProject\Proxies\__CG__\stdClass;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 * @ORM\Entity(repositoryClass="WeavingTheWeb\Bundle\ApiBundle\Repository\WhispererRepository")
 * @ORM\Table(name="weaving_whisperer",
 *  uniqueConstraints={
 *      @ORM\UniqueConstraint(
 *          name="unique_name",
 *          columns={"name"}),
 *  },
 *  indexes={
 *      @ORM\Index(
 *          name="name",
 *          columns={"name"}
 *      )
 *  }
 * )
 */
class Whisperer
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    public function getId()
    {
        return $this->id;
    }

    /**
     * Whisperer constructor.
     * @param $name
     * @param int $whispers
     */
    public function __construct($name, $whispers = 0)
    {
        $this->name = $name;
        $this->whispers = $whispers;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @var integer
     *
     * @ORM\Column(name="whispers", type="integer", options={"default": 0})
     */
    private $whispers = 0;

    public function getWhispers()
    {
        return $this->whispers;
    }

    /**
     * @param $whispers
     * @return $this
     */
    public function setWhispers($whispers)
    {
        $this->whispers = $whispers;

        return $this;
    }

    /**
     * @var integer
     *
     * @ORM\Column(name="previous_whispers", type="integer", options={"default": 0})
     */
    private $previousWhispers = 0;

    /**
     * @return int
     */
    public function getPreviousWhispers()
    {
        return $this->previousWhispers;
    }

    /**
     * @param $whispers
     * @return $this
     */
    public function setPreviousWhispers($whispers)
    {
        $this->previousWhispers = $whispers;

        return $this;
    }

    /**
     * @var integer
     *
     * @ORM\Column(name="expected_whispers", type="integer", options={"default": 0})
     */
    private $expectedWhispers = 0;

    /**
     * @param $whispers
     * @return $this
     */
    public function setExpectedWhispers($whispers)
    {
        $this->expectedWhispers = $whispers;
        
        return $this;
    }

    /**
     * @return int
     */
    public function getExpectedWhispers()
    {
        return $this->expectedWhispers;
    }
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @param \DateTime $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

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
     * @var stdClass
     */
    public $member;
}
