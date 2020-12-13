<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Api\Entity;

use App\Twitter\Infrastructure\Api\Exception\InvalidSerializedTokenException;
use App\Membership\Domain\Entity\Legacy\Member;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use function array_key_exists;

/**
 * @package App\Twitter\Infrastructure\Api\Entity
 *
 * @ORM\Table(name="weaving_access_token")
 * @ORM\Entity(repositoryClass="App\Twitter\Infrastructure\Api\AccessToken\Repository\TokenRepositoryInterface")
 */
class Token implements TokenInterface
{
    use TokenTrait;

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
     * @param string $oauthToken
     *
     * @return Token
     */
    public function setOAuthToken($oauthToken): self
    {
        $this->oauthToken = $oauthToken;

        return $this;
    }

    /**
     * @return string
     */
    public function getOAuthToken(): string
    {
        return $this->oauthToken;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="secret", type="string", length=255, nullable=true)
     */
    protected ?string $oauthTokenSecret;

    /**
     * @var string
     *
     * @ORM\Column(name="consumer_key", type="string", length=255, nullable=true)
     */
    public ?string $consumerKey = null;

    public function getConsumerKey(): string
    {
        return $this->consumerKey;
    }

    public function setConsumerKey(?string $consumerKey): self
    {
        $this->consumerKey = $consumerKey;

        return $this;
    }

    public function hasConsumerKey(): bool
    {
        return $this->consumerKey !== null;
    }

    /**
     * @var string
     *
     * @ORM\Column(name="consumer_secret", type="string", length=255, nullable=true)
     */
    public ?string $consumerSecret = null;

    public function getConsumerSecret(): string
    {
        return $this->consumerSecret;
    }

    public function setConsumerSecret(?string $consumerSecret): self
    {
        $this->consumerSecret = $consumerSecret;

        return $this;
    }

    /**
     * @ORM\Column(name="frozen_until", type="datetime", nullable=true)
     */
    protected ?DateTimeInterface $frozenUntil;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected ?DateTimeInterface $createdAt;

    /**
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    protected ?DateTimeInterface $updatedAt;

    /**
     * @ORM\ManyToMany(targetEntity="App\Membership\Domain\Entity\Legacy\Member", mappedBy="tokens")
     */
    protected Collection $users;

    /**
     * @var boolean
     */
    protected ?bool $frozen;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $oauthTokenSecret
     *
     * @return Token
     */
    public function setOauthTokenSecret($oauthTokenSecret)
    {
        $this->oauthTokenSecret = $oauthTokenSecret;

        return $this;
    }

    /**
     * @return string
     */
    public function getOauthTokenSecret()
    {
        return $this->getOAuthSecret();
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
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
     *
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

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    /**
     * @param Member $users
     *
     * @return Token
     */
    public function addUser(Member $users)
    {
        $this->users[] = $users;

        return $this;
    }

    /**
     * @param Member $users
     */
    public function removeUser(Member $users)
    {
        $this->users->removeElement($users);
    }

    /**
     * @return Collection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param DateTimeInterface $frozenUntil
     *
     * @return $this
     */
    public function setFrozenUntil(DateTimeInterface $frozenUntil): self
    {
        $this->frozenUntil = $frozenUntil;

        return $this;
    }

    public function getFrozenUntil(): \DateTimeInterface
    {
        return $this->frozenUntil;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getOAuthToken();
    }

    /**
     * @param TokenType $type
     *
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
     *
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
    public function isFrozen(): bool
    {
        return $this->frozen;
    }

    /**
     * @return bool
     */
    public function isNotFrozen(): bool
    {
        return !$this->isFrozen();
    }

    public function getOAuthSecret(): string
    {
        return $this->oauthTokenSecret;
    }

    public function setOAuthSecret($oauthSecret): self
    {
        $this->oauthTokenSecret = $oauthSecret;

        return $this;
    }

    /**
     * @param array $properties
     *
     * @return static
     * @throws InvalidSerializedTokenException
     */
    public static function fromArray(array $properties): self
    {
        $token = new self();

        if (!array_key_exists('token', $properties)) {
            InvalidSerializedTokenException::throws('A token is required');
        }

        if (!array_key_exists('secret', $properties)) {
            InvalidSerializedTokenException::throws('A secret is required');
        }

        $consumerKey = null;
        if (array_key_exists('consumer_key', $properties)) {
            $consumerKey = $properties['consumer_key'];
        }

        $consumerSecret = null;
        if (array_key_exists('consumer_secret', $properties)) {
            $consumerSecret = $properties['consumer_secret'];
        }

        $token->setOAuthToken($properties['token']);
        $token->setOAuthSecret($properties['secret']);
        $token->setConsumerKey($consumerKey);
        $token->setConsumerSecret($consumerSecret);

        return $token;
    }

    public function isValid(): bool
    {
        return $this->getOAuthToken() !== null
            && $this->getOAuthSecret() !== null;
    }

    public function firstIdentifierCharacters(): string
    {
        return substr($this->getOAuthToken(), 0, 8);
    }
}
