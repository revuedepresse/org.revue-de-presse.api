<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Entity;

use App\Security\AccessTokenInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use WTW\UserBundle\Entity\User;

/**
 * @ORM\Table(name="weaving_access_token")
 * @ORM\Entity(repositoryClass="WeavingTheWeb\Bundle\ApiBundle\Repository\TokenRepository")
 */
class Token implements AccessTokenInterface
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
     * @var string
     *
     * @ORM\ManyToOne(targetEntity="TokenType", inversedBy="tokens", cascade={"all"})
     * @ORM\JoinColumn(name="type", referencedColumnName="id")
     */
    protected $type;

    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=255)
     */
    protected $oauthToken;

    /**
     * @var string
     *
     * @ORM\Column(name="secret", type="string", length=255, nullable=true)
     */
    protected $oauthTokenSecret;

    /**
     * @var string
     *
     * @ORM\Column(name="consumer_key", type="string", length=255, nullable=true)
     */
    public $consumerKey;

    /**
     * @return string
     */
    public function getConsumerKey()
    {
        return $this->consumerKey;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="consumer_secret", type="string", length=255, nullable=true)
     */
    public $consumerSecret;

    /**
     * @return string
     */
    public function getConsumerSecret()
    {
        return $this->consumerSecret;
    }

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="frozen_until", type="datetime", nullable=true)
     */
    protected $frozenUntil;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    protected $updatedAt;

    /**
     * @ORM\ManyToMany(targetEntity="WTW\UserBundle\Entity\User", mappedBy="tokens")
     */
    protected $users;

    /**
     * @var boolean
     */
    protected $frozen;

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
     * Set oauthToken
     *
     * @param string $oauthToken
     * @return Token
     */
    public function setOauthToken($oauthToken)
    {
        $this->oauthToken = $oauthToken;
    
        return $this;
    }

    /**
     * @return string
     * @deprecated
     */
    public function getOauthToken()
    {
        return $this->getAccessToken();
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->oauthToken;
    }

    /**
     * Set oauthTokenSecret
     *
     * @param string $oauthTokenSecret
     * @return Token
     */
    public function setOauthTokenSecret($oauthTokenSecret)
    {
        $this->oauthTokenSecret = $oauthTokenSecret;
    
        return $this;
    }

    /**
     * @return string
     * @deprecated
     */
    public function getOauthTokenSecret()
    {
        return $this->oauthTokenSecret;
    }

    /**
     * @return string
     */
    public function getAccessTokenSecret()
    {
        return $this->oauthTokenSecret;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Token
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
     * @param \DateTime $updatedAt
     * @return Token
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
     * Constructor
     */
    public function __construct()
    {
        $this->users = new ArrayCollection();
    }
    
    /**
     * Add users
     *
     * @param \WTW\UserBundle\Entity\User $users
     * @return Token
     */
    public function addUser(User $users)
    {
        $this->users[] = $users;
    
        return $this;
    }

    /**
     * Remove users
     *
     * @param \WTW\UserBundle\Entity\User $users
     */
    public function removeUser(User $users)
    {
        $this->users->removeElement($users);
    }

    /**
     * Get users
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param \DateTime $frozenUntil
     */
    public function setFrozenUntil($frozenUntil)
    {
        $this->frozenUntil = $frozenUntil;
    }

    /**
     * @return \DateTime
     */
    public function getFrozenUntil()
    {
        return $this->frozenUntil;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getOauthToken();
    }

    /**
     * Set type
     *
     * @param \WeavingTheWeb\Bundle\ApiBundle\Entity\TokenType $type
     * @return Token
     */
    public function setType(TokenType $type = null)
    {
        $this->type = $type;
    
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param $frozen
     * @return $this
     */
    public function setFrozen($frozen)
    {
        $this->frozen = $frozen;

        return $this;
    }

    /**
     * @return bool
     */
    public function isFrozen()
    {
        return $this->frozen;
    }

    /**
     * @return bool
     */
    public function isNotFrozen()
    {
        return !$this->isFrozen();
    }
}
