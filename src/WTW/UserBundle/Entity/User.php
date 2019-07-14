<?php

namespace WTW\UserBundle\Entity;

use App\Member\MemberInterface;

use App\Serialization\JsonEncodingAwareInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use WTW\UserBundle\Model\User as BaseUser;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 * @package WTW\UserBundle\Entity
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
 * @ORM\Entity(repositoryClass="WTW\UserBundle\Repository\UserRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="usr_position_in_hierarchy", type="integer")
 * @ORM\DiscriminatorMap({"1" = "User", "0" = "\WTW\UserBundle\Tests\Security\Core\User\User"})
 */
class User extends BaseUser implements MemberInterface, JsonEncodingAwareInterface
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="usr_id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="usr_twitter_id", type="string", length=255, nullable=true)
     */
    protected $twitterID;

    /**
     * @var string
     *
     * @ORM\Column(name="usr_twitter_username", type="string", nullable=true)
     */
    protected $twitter_username;

    /**
     * @var integer
     *
     * @ORM\Column(name="usr_avatar", type="integer", nullable=true)
     */
    protected $avatar;

    /**
     * @var string
     *
     * @ORM\Column(name="usr_full_name", type="string", length=255, nullable=true)
     */
    protected $fullName;

    /**
     * @var boolean
     *
     * @ORM\Column(name="usr_status", type="boolean", nullable=false)
     */
    protected $enabled;

    /**
     * @var string
     *
     * @ORM\Column(name="usr_user_name", type="string", length=255, nullable=true)
     */
    protected $username;

    /**
     * @var string
     *
     * @ORM\Column(name="usr_username_canonical", type="string", length=255, nullable=true)
     */
    protected $usernameCanonical;

    /**
     * @var string
     *
     * @ORM\Column(name="usr_email", type="string", length=255)
     */
    protected $email;

    /**
     * @var string
     * @ORM\Column(name="usr_email_canonical", type="string", length=255, nullable=true)
     */
    protected $emailCanonical;

    /**
     * @var \DateTime
     */
    protected $positionInHierarchy;

    /**
     * @ORM\ManyToMany(targetEntity="WeavingTheWeb\Bundle\ApiBundle\Entity\Token", inversedBy="users", fetch="EAGER")
     * @ORM\JoinTable(name="weaving_user_token",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="usr_id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="token_id", referencedColumnName="id")}
     * )
     */
    protected $tokens;

    /**
     * @ORM\Column(name="usr_api_key", type="string", nullable=true)
     */
    public $apiKey;

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
     * Get userName
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set avatar
     *
     * @param integer $avatar
     * @return User
     */
    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * Get avatar
     *
     * @return integer
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
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
     * Set enabled
     *
     * @param boolean $enabled
     * @return User
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get enabled
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enabled;
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

    /**
     * Set usernameCanonical
     *
     * @param string $usernameCanonical
     * @return User
     */
    public function setUsernameCanonical($usernameCanonical)
    {
        $this->usernameCanonical = $usernameCanonical;

        return $this;
    }

    /**
     * Get usernameCanonical
     *
     * @return string
     */
    public function getUsernameCanonical()
    {
        return $this->usernameCanonical;
    }

    /**
     * Set emailCanonical
     *
     * @param string $emailCanonical
     * @return User
     */
    public function setEmailCanonical($emailCanonical)
    {
        $this->emailCanonical = $emailCanonical;

        return $this;
    }

    /**
     * Get emailCanonical
     *
     * @return string
     */
    public function getEmailCanonical()
    {
        return $this->emailCanonical;
    }

    /**
     * Set locked
     *
     * @param boolean $locked
     * @return User
     */
    public function setLocked($locked)
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * Get locked
     *
     * @return boolean
     */
    public function getLocked()
    {
        return $this->locked;
    }

    /**
     * Set expired
     *
     * @param boolean $expired
     * @return User
     */
    public function setExpired($expired)
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
     * @param string $confirmationToken
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

    /**
     * Set username
     *
     * @param string $username
     * @return User
     */
    public function setUsername($username): MemberInterface
    {
        $this->username = $username;

        return $this;
    }

    public function __construct()
    {
        parent::__construct();

        $this->tokens = new ArrayCollection();
        $this->publicationFrequencies = new ArrayCollection();
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
     * @param \WeavingTheWeb\Bundle\ApiBundle\Entity\Token $tokens
     * @return User
     */
    public function addToken(\WeavingTheWeb\Bundle\ApiBundle\Entity\Token $tokens)
    {
        $this->tokens[] = $tokens;

        return $this;
    }

    /**
     * Remove tokens
     *
     * @param \WeavingTheWeb\Bundle\ApiBundle\Entity\Token $tokens
     */
    public function removeToken(\WeavingTheWeb\Bundle\ApiBundle\Entity\Token $tokens)
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
    protected $protected = false;

    /**
     * @param boolean $protected
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
    public $maxStatusId;

    /**
     * @ORM\Column(name="min_status_id", type="string", length=255, nullable=true)
     */
    public $minStatusId;

    /**
     * @ORM\Column(name="max_like_id", type="string", length=255, nullable=true)
     */
    public $maxLikeId;

    /**
     * @ORM\Column(name="min_like_id", type="string", length=255, nullable=true)
     */
    public $minLikeId;

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
    public $description = '';

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
    public $url;

    /**
     * @return string
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_status_publication_date", type="datetime", nullable=true)
     */
    public $lastStatusPublicationDate = null;

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
     * @return false|string
     */
    public function encodeAsJson(): string
    {
        $jsonEncodedMember = json_encode([
            'id' => $this->id,
            'username' => $this->username,
            'description' => $this->description,
            'url' => $this->url,
        ]);

        if (!$jsonEncodedMember) {
            return json_encode([
                'id' => 0,
                'username' => '',
                'description' => '',
                'url' => '',
            ]);
        }

        return $jsonEncodedMember;
    }

    /**
     * @return bool
     */
    public function isAWhisperer(): bool
    {
        $oneMonthAgo = new \DateTime('now', new \DateTimeZone('UTC'));
        $oneMonthAgo->modify('-1 month');

        return !is_null($this->lastStatusPublicationDate) &&
            ($this->lastStatusPublicationDate < $oneMonthAgo);
    }

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(
     *     targetEntity="\App\Analysis\Entity\PublicationFrequency",
     *     mappedBy="member"
     * )
     */
    private $publicationFrequencies;
}
