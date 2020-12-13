<?php

namespace App\Twitter\Infrastructure\Api\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="weaving_token_type")
 * @ORM\Entity()
 */
class TokenType
{
    const USER = 'user';

    const APPLICATION = 'application';

    public function __construct()
    {
        $this->tokens = new ArrayCollection();
    }

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

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
     * Set name
     *
     * @param string $name
     * @return TokenType
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
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="Token", mappedBy="type")
     */
    protected $tokens;

    public function getTokens()
    {
        return $this->tokens;
    }

    /**
     * @param $token
     */
    public function addToken($token)
    {
        $this->tokens->add($token);
    }

    /**
     * @param $token
     */
    public function removeToken($token)
    {
        $this->tokens->remove($token);
    }

}
