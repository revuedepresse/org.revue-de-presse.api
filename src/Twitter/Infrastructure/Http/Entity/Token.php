<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\Entity;

use App\Twitter\Domain\Http\Model\TokenInterface;
use App\Twitter\Infrastructure\Http\Exception\UnexpectedAccessTokenProperties;
use App\Membership\Infrastructure\Entity\Legacy\Member;
use Assert\Assert;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use function array_key_exists;

/**
 * @ORM\Table(name="weaving_access_token")
 * @ORM\Entity
 */
class Token implements TokenInterface
{
    use TokenTrait;

    const EXPECTED_SECRET = 'A secret is required';
    const EXPECTED_SECRET_TYPE = 'A secret is expected to be a string.';
    const EXPECTED_SECRET_LENGTH = 'A secret is expected to be non-empty.';

    const EXPECTED_TOKEN = 'A token is required';
    const EXPECTED_TOKEN_TYPE = 'A token is expected to be a string.';
    const EXPECTED_TOKEN_LENGTH = 'A token is expected to be non-empty.';

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $id;

    /**
     * @ORM\ManyToOne(targetEntity="TokenType", inversedBy="tokens", cascade={"all"})
     * @ORM\JoinColumn(name="type", referencedColumnName="id")
     */
    protected TokenType $type;

    /**
     * @ORM\Column(name=self::TOKEN, type="string", length=255)
     */
    protected string $oauthToken;

    public function setAccessToken(string $accessToken): TokenInterface
    {
        $this->oauthToken = $accessToken;

        return $this;
    }

    public function getAccessToken(): string
    {
        return $this->oauthToken;
    }

    /**
     * @ORM\Column(name=self::SECRET, type="string", length=255, nullable=true)
     */
    protected ?string $oauthTokenSecret;

    public function setAccessTokenSecret(string $accessTokenSecret): TokenInterface
    {
        $this->oauthTokenSecret = $accessTokenSecret;

        return $this;
    }

    public function getAccessTokenSecret(): string
    {
        return $this->oauthTokenSecret;
    }

    /**
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
    protected ?DateTimeInterface $frozenUntil = null;

    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected ?DateTimeInterface $createdAt;

    /**
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    protected ?DateTimeInterface $updatedAt;

    /**
     * @ORM\ManyToMany(targetEntity="App\Membership\Infrastructure\Entity\Legacy\Member", mappedBy="tokens")
     */
    protected Collection $users;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set createdAt
     *
     * @param DateTime $createdAt
     *
     * @return Token
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setUpdatedAt(DateTimeInterface $updatedAt): TokenInterface
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function updatedAt(): DateTimeInterface
    {
        return $this->updatedAt;
    }

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
    protected function setFrozenUntil(DateTimeInterface $frozenUntil): self
    {
        $this->frozenUntil = $frozenUntil;

        return $this;
    }

    public function getFrozenUntil(): ?\DateTimeInterface
    {
        return $this->frozenUntil;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getAccessToken();
    }

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
     * @return bool
     * @throws Exception
     */
    public function isFrozen(): bool
    {
        return $this->getFrozenUntil() !== null &&
            $this->getFrozenUntil()->getTimestamp() >
                (new DateTime('now', new DateTimeZone('UTC')))
                    ->getTimestamp();
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function isNotFrozen(): bool
    {
        return !$this->isFrozen();
    }

    /**
     * @throws UnexpectedAccessTokenProperties
     */
    public static function fromProps(array $accessTokenProps): self
    {
        $accessToken = new self();

        try {
            if (!array_key_exists(self::FIELD_TOKEN, $accessTokenProps)) {
                UnexpectedAccessTokenProperties::throws(self::EXPECTED_TOKEN);
            }

            Assert::lazy()
                ->that($accessTokenProps[self::FIELD_TOKEN])
                    ->string(self::EXPECTED_TOKEN_TYPE)
                    ->notEmpty(self::EXPECTED_TOKEN_LENGTH)
                ->tryAll()
                ->verifyNow();

            if (!array_key_exists(self::FIELD_SECRET, $accessTokenProps)) {
                UnexpectedAccessTokenProperties::throws(self::EXPECTED_SECRET);
            }

            Assert::lazy()
                ->that($accessTokenProps[self::FIELD_SECRET])
                    ->string(self::EXPECTED_SECRET_TYPE)
                    ->notEmpty(self::EXPECTED_SECRET_LENGTH)
                ->tryAll()
                ->verifyNow();
        } catch (\Exception $exception) {
            UnexpectedAccessTokenProperties::throws(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }

        $consumerKey = null;
        if (array_key_exists('consumer_key', $accessTokenProps)) {
            $consumerKey = $accessTokenProps['consumer_key'];
        }

        $consumerSecret = null;
        if (array_key_exists('consumer_secret', $accessTokenProps)) {
            $consumerSecret = $accessTokenProps['consumer_secret'];
        }

        $accessToken->setAccessToken($accessTokenProps[self::FIELD_TOKEN]);
        $accessToken->setAccessTokenSecret($accessTokenProps[self::FIELD_SECRET]);
        $accessToken->setConsumerKey($consumerKey);
        $accessToken->setConsumerSecret($consumerSecret);

        return $accessToken;
    }

    public function isValid(): bool
    {
        return $this->getAccessToken() !== null
            && $this->getAccessTokenSecret() !== null;
    }

    public function firstIdentifierCharacters(): string
    {
        return substr($this->getAccessToken(), 0, 8);
    }

    /** The token is frozen when the "frozen until" date is in the future */
    public function freeze(): TokenInterface
    {
        return $this->setFrozenUntil($this->nextFreezeEndsAt());
    }

    /**
     * @throws \Exception
     */
    public function unfreeze(): TokenInterface
    {
        return $this->setFrozenUntil(
            new DateTimeImmutable(
                'now - 15min',
                new DateTimeZone('UTC')
            )
        );
    }

    /**
     * @throws \Exception
     */
    public function nextFreezeEndsAt(): DateTimeInterface
    {
        return new DateTimeImmutable(
            'now + 15min',
            new DateTimeZone('UTC')
        );
    }
}
