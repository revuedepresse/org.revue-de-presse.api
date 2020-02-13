<?php
declare(strict_types=1);

namespace App\Membership\Entity;

use App\Api\Entity\Token;
use App\Membership\Model\Member as MemberModel;

use DateTime;
use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 *
 * @ORM\Table(
 *      name="weaving_user",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="unique_twitter_id", columns={"usr_twitter_id"}),
 *      },
 *      indexes={
 *          @ORM\Index(name="membership", columns={
 *              "usr_id",
 *              "usr_twitter_id",
 *              "usr_twitter_username",
 *              "not_found",
 *              "protected",
 *              "suspended",
 *              "total_subscribees",
 *              "total_subscriptions"
 *          })
 *      }
 * )
 * @ORM\Entity(repositoryClass="App\Membership\Repository\MemberRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="usr_position_in_hierarchy", type="integer")
 * @ORM\DiscriminatorMap({"1" = "Member", "0" = "\App\Tests\Security\Core\Member\Member"})
 * @package App\Membership\Entity
 */
class Member extends MemberModel
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="usr_id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected int $id;

    /**
    * @var string
    *
    * @ORM\Column(name="usr_twitter_id", type="string", length=255, nullable=true)
    */
    protected ?string $twitterID;

    /**
     * @var string
     *
     * @ORM\Column(name="usr_twitter_username", type="string", nullable=true)
     */
    protected string $twitter_username;

    /**
     * @var string
     *
     * @ORM\Column(name="usr_avatar", type="integer", nullable=true)
     */
    protected ?string $avatar;

    /**
     * @var string
     *
     * @ORM\Column(name="usr_full_name", type="string", length=255, nullable=true)
     */
    protected ?string $fullName;

    /**
     * @var boolean
     *
     * @ORM\Column(name="usr_status", type="boolean", nullable=false)
     */
    protected bool $enabled;

    /**
     * @var string
     *
     * @ORM\Column(name="usr_user_name", type="string", length=255, nullable=true)
     */
    protected ?string $username;

    /**
     * @var string
     *
     * @ORM\Column(name="usr_username_canonical", type="string", length=255, nullable=true)
     */
    protected ?string $usernameCanonical;

    /**
     * @var string
     *
     * @ORM\Column(name="usr_email", type="string", length=255)
     */
    protected string $email;

    /**
     * @var string
     * @ORM\Column(name="usr_email_canonical", type="string", length=255, nullable=true)
     */
    protected ?string $emailCanonical;

    /**
     * @var int
     */
    protected int $positionInHierarchy;

    /**
     * @ORM\ManyToMany(
     *      targetEntity="\App\Api\Entity\Token",
     *      inversedBy="users",
     *      fetch="EAGER"
     * )
     * @ORM\JoinTable(name="weaving_user_token",
     *      joinColumns={
     *          @ORM\JoinColumn(name="user_id", referencedColumnName="usr_id")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="token_id", referencedColumnName="id")
     *      }
     * )
     */
    protected Selectable $tokens;

    /**
     * @ORM\Column(name="usr_api_key", type="string", nullable=true)
     */
    public ?string $apiKey;

    /**
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * @return integer
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @param string $avatar
     *
     * @return $this
     */
    public function setAvatar(string $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * @return string
     */
    public function getAvatar(): string
    {
        return $this->avatar;
    }

    /**
     * Set password
     *
     * @param  string $password
     * @return Member
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $twitterId
     * @return MemberInterface
     */
    public function setTwitterID(string $twitterId): MemberInterface
    {
      $this->twitterID = $twitterId;

      return $this;
    }

    /**
     * @param $twitterUsername
     * @return $this
     */
    public function setTwitterUsername(string $twitterUsername): MemberInterface
    {
        $this->twitter_username = $twitterUsername;

        return $this;
    }

    /**
    * Get twitter_username
    *
    * @return string
    */
    public function getTwitterUsername(): string
    {
      return $this->twitter_username;
    }

    /**
     * @return string
     */
    public function getTwitterID(): ?string
    {
        return $this->twitterID;
    }

    /**
     * @param string $fullName
     * @return MemberInterface
     */
    public function setFullName(string $fullName): MemberInterface
    {
        $this->fullName = $fullName;

        return $this;
    }

    /**
     * @return string
     */
    public function getFullName(): string
    {
        return $this->fullName;
    }

    private bool $locked;

    /**
     * @param $locked
     *
     * @return self
     */
    public function setLocked($locked): self
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * @return bool
     */
    public function getLocked(): bool
    {
        return $this->locked;
    }

    /**
     * @param bool $expired
     *
     * @return $this
     */
    public function setExpired(bool $expired): self
    {
        $this->expired = $expired;

        return $this;
    }

    /**
     * Get expired
     *
     * @return boolean
     */
    public function getExpired()
    {
        return $this->expired;
    }

    /**
     * Set confirmationToken
     *
     * @param  string $confirmationToken
     * @return User
     */
    public function setConfirmationToken($confirmationToken)
    {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }

    /**
     * Get confirmationToken
     *
     * @return string
     */
    public function getConfirmationToken()
    {
        return $this->confirmationToken;
    }

    public function __construct()
    {
        parent::__construct();

        $this->tokens = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get enabled
     *
     * @return boolean 
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Add tokens
     *
     * @param \App\Api\Entity\Token $tokens
     * @return User
     */
    public function addToken(Token $tokens)
    {
        $this->tokens[] = $tokens;
    
        return $this;
    }

    /**
     * Remove tokens
     *
     * @param \App\Api\Entity\Token $tokens
     */
    public function removeToken(Token $tokens)
    {
        $this->tokens->removeElement($tokens);
    }

    /**
     * Get tokens
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getTokens()
    {
        return $this->tokens;
    }

    /**
     * Protected status according to Twitter
     *
     * @var boolean
     * @ORM\Column(name="protected", type="boolean", options={"default": false}, nullable=true)
     */
    protected ?bool $protected = false;

    /**
     * @param  boolean $protected
     * @return User
     */
    public function setProtected(bool $protected): MemberInterface
    {
        $this->protected = $protected;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isProtected(): bool
    {
        return $this->protected;
    }

    /**
     * @return boolean
     */
    public function isNotProtected(): bool
    {
        return !$this->isProtected();
    }

    /**
     * Suspended status according to Twitter
     *
     * @var boolean
     * @ORM\Column(name="suspended", type="boolean", options={"default": false})
     */
    protected $suspended = false;

    /**
     * @param bool $suspended
     * @return MemberInterface
     */
    public function setSuspended(bool $suspended): MemberInterface
    {
        $this->suspended = $suspended;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isSuspended(): bool
    {
        return $this->suspended;
    }

    /**
     * @return boolean
     */
    public function isNotSuspended(): bool
    {
        return !$this->isSuspended();
    }

    /**
     * NotFound status according to Twitter
     *
     * @var boolean
     * @ORM\Column(name="not_found", type="boolean", options={"default": false})
     */
    protected $notFound = false;

    /**
     * @param bool $notFound
     * @return MemberInterface
     */
    public function setNotFound(bool $notFound): MemberInterface
    {
        $this->notFound = $notFound;

        return $this;
    }

    /**
     * @return boolean
     * @deprecated in favor of ->hasBeenDeclaredAsNotFound
     */
    public function isNotFound(): bool
    {
        return $this->hasBeenDeclaredAsNotFound();
    }

    /**
     * @return boolean
     */
    public function hasBeenDeclaredAsNotFound(): bool
    {
        return $this->notFound;
    }

    /**
     * @return boolean
     */
    public function hasNotBeenDeclaredAsNotFound(): bool
    {
        return !$this->hasBeenDeclaredAsNotFound();
    }

    /**
     * @ORM\Column(name="max_status_id", type="string", length=255, nullable=true)
     */
    public ?string $maxStatusId;

    /**
     * @ORM\Column(name="min_status_id", type="string", length=255, nullable=true)
     */
    public ?string $minStatusId;

    /**
     * @ORM\Column(name="max_like_id", type="string", length=255, nullable=true)
     */
    public ?string $maxLikeId;

    /**
     * @ORM\Column(name="min_like_id", type="string", length=255, nullable=true)
     */
    public ?string $minLikeId;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_statuses", type="integer", options={"default": 0})
     */
    public $totalStatuses = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_likes", type="integer", options={"default": 0})
     */
    public $totalLikes = 0;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    public ?string $description = '';

    /**
     * @return null|string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @ORM\Column(name="url", type="text", nullable=true)
     */
    public ?string $url;

    /**
     * @return string
     */
    public function getUrl(): ?string {
        return $this->url;
    }

    /**
     * @var DateTime
     *
     * @ORM\Column(name="last_status_publication_date", type="datetime", nullable=true)
     */
    public ?DateTime $lastStatusPublicationDate = null;

    /**
     * @var integer
     * @ORM\Column(name="total_subscribees", type="integer", options={"default": 0})
     */
    public $totalSubscribees = 0;

    /**
     * @var integer
     * @ORM\Column(name="total_subscriptions", type="integer", options={"default": 0})
     */
    public $totalSubscriptions = 0;

    /**
     * @return bool
     * @throws \Exception
     */
    public function isAWhisperer(): bool
    {
        $oneMonthAgo = new DateTime('now', new \DateTimeZone('UTC'));
        $oneMonthAgo->modify('-1 month');

        return !is_null($this->lastStatusPublicationDate) &&
            ($this->lastStatusPublicationDate < $oneMonthAgo);
    }
}
