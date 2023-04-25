<?php
declare(strict_types=1);

namespace App\Membership\Infrastructure\Entity\Legacy;

use App\Membership\Domain\Model\Member as MemberModel;
use App\Twitter\Infrastructure\Http\Entity\Token;
use App\Twitter\Infrastructure\Serialization\JsonEncodingAwareInterface;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\Mapping as ORM;
use const JSON_THROW_ON_ERROR;

/**
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
 * @ORM\Entity(repositoryClass="App\Membership\Infrastructure\Repository\MemberRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="usr_position_in_hierarchy", type="integer")
 * @ORM\DiscriminatorMap({"1" = "Member"})
 */
class Member extends MemberModel implements JsonEncodingAwareInterface
{
    /**
     * @ORM\Column(name="usr_api_key", type="string", nullable=true)
     */
    public ?string $apiKey = null;

    /**
     * @ORM\Column(name="max_status_id", type="string", length=255, nullable=true)
     */
    public ?string $maxStatusId;

    public function maxTweetId(): int
    {
        return intval($this->maxStatusId);
    }

    public function setMaxTweetId(?int $tweetId): MemberInterface
    {
        $this->maxStatusId = (string) $tweetId;

        return $this;
    }

    /**
     * @ORM\Column(name="min_status_id", type="string", length=255, nullable=true)
     */
    public ?string $minStatusId;

    public function minTweetId(): int
    {
        return (int) $this->minStatusId;
    }

    public function setMinTweetId(?int $minTweetId): MemberInterface
    {
        $this->minStatusId = (string) $minTweetId;

        return $this;
    }

    private ?string $rawDocument = '{}';

    public function rawDocument(): array {
        return json_decode($this->rawDocument, true);
    }

    /**
     * @ORM\Column(name="max_like_id", type="string", length=255, nullable=true)
     */
    public ?string $maxLikeId;

    /**
     * @ORM\Column(name="min_like_id", type="string", length=255, nullable=true)
     */
    public ?string $minLikeId;

    /**
     * @ORM\Column(name="total_statuses", type="integer", options={"default": 0})
     */
    public int $totalStatuses = 0;

    /**
     * @ORM\Column(name="total_likes", type="integer", options={"default": 0})
     */
    public $totalLikes = 0;

    public function totalLikes(): int
    {
        return $this->totalLikes;
    }

    public function setTotalLikes(int $totalLikes): self
    {
        $this->totalLikes = $totalLikes;

        return $this;
    }

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    public ?string $description = '';

    /**
     * @ORM\Column(name="url", type="text", nullable=true)
     */
    public ?string $url = null;

    /**
     * @ORM\Column(name="last_status_publication_date", type="datetime", nullable=true)
     */
    public ?\DateTimeInterface $lastStatusPublicationDate = null;

    public function setLastStatusPublicationDate(\DateTimeInterface $lastStatusPublicationDate): self
    {
        $this->lastStatusPublicationDate = $lastStatusPublicationDate;

        return $this;
    }

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
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="usr_id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected ?int $id;

    /**
     * @ORM\Column(name="usr_twitter_id", type="string", length=255, nullable=true)
     */
    protected ?string $twitterID;

    public function setTwitterID(string $twitterId): self
    {
        $this->twitterID = $twitterId;

        return $this;
    }

    public function twitterId(): ?string
    {
        return $this->twitterID;
    }

    /**
     * @ORM\Column(name="usr_twitter_username", type="string", nullable=true)
     */
    protected ?string $twitter_username;

    public function setTwitterScreenName(string $twitterScreenName): self
    {
        $this->twitter_username = strtolower($twitterScreenName);

        return $this;
    }

    public function twitterScreenName(): ?string
    {
        return strtolower($this->twitter_username);
    }

    /**
     * @var string
     *
     * @ORM\Column(name="usr_avatar", type="string", nullable=true)
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
     *      targetEntity="\App\Twitter\Infrastructure\Http\Entity\Token",
     *      inversedBy="users",
     *      fetch="EAGER",
     *      cascade={"persist"},
     *      indexBy="id"
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
     * Protected status according to Twitter
     *
     * @var boolean
     * @ORM\Column(name="protected", type="boolean", options={"default": false}, nullable=true)
     */
    protected ?bool $protected = false;

    /**
     * Suspended status according to Twitter
     *
     * @ORM\Column(name="suspended", type="boolean", options={"default": false})
     */
    protected bool $suspended = false;

    /**
     * NotFound status according to Twitter
     *
     * @ORM\Column(name="not_found", type="boolean", options={"default": false})
     */
    protected bool $notFound = false;

    private bool $locked;

    /**
     * @ORM\OneToMany(
     *      targetEntity="\App\Twitter\Infrastructure\Curation\Entity\PublicationBatchCollectedEvent",
     *      mappedBy="member"
     * )
     */
    protected Collection $publicationBatchCollectedEvents;

    public function __construct()
    {
        parent::__construct();

        $this->tokens = new ArrayCollection();
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->twitterId();
    }

    /**
     * @return false|string
     */
    public function encodeAsJson(): string
    {
        $jsonEncodedMember = json_encode(
            [
                'id'          => $this->id,
                'username'    => $this->twitter_username,
                'description' => $this->description,
                'url'         => $this->url,
            ],
            JSON_THROW_ON_ERROR
        );

        if (!$jsonEncodedMember) {
            return json_encode(
                [
                    'id'          => 0,
                    'username'    => '',
                    'description' => '',
                    'url'         => '',
                ],
                JSON_THROW_ON_ERROR
            );
        }

        return $jsonEncodedMember;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function getAvatar(): string
    {
        return $this->avatar;
    }

    public function setAvatar(string $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    public function getExpired()
    {
        return $this->expired;
    }

    public function getFullName(): string
    {
        if ($this->fullName === null) {
            return '';
        }

        return $this->fullName;
    }

    /**
     * @deprecated in favor of ->setName
     *
     */
    public function setFullName(string $fullName): self
    {
        return $this->setName($fullName);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLocked(): bool
    {
        return $this->locked;
    }

    public function setLocked($locked): self
    {
        $this->locked = $locked;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getTokens(): Selectable
    {
        return $this->tokens;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function hasBeenDeclaredAsNotFound(): bool
    {
        return $this->notFound;
    }

    public function hasNotBeenDeclaredAsNotFound(): bool
    {
        return !$this->hasBeenDeclaredAsNotFound();
    }

    /**
     * @throws \Exception
     * @deprecated in favor of ->isLowVolumeTweetWriter
     */
    public function isAWhisperer(): bool
    {
        $oneMonthAgo = new DateTime('now', new \DateTimeZone('UTC'));
        $oneMonthAgo->modify('-1 month');

        return !is_null($this->lastStatusPublicationDate)
            && ($this->lastStatusPublicationDate < $oneMonthAgo);
    }

    public function isLowVolumeTweetWriter(): bool {
        return $this->isAWhisperer();
    }

    /**
     * @deprecated in favor of ->hasBeenDeclaredAsNotFound
     */
    public function isNotFound(): bool
    {
        return $this->hasBeenDeclaredAsNotFound();
    }

    public function setNotFound(bool $notFound): self
    {
        $this->notFound = $notFound;

        return $this;
    }

    public function isNotProtected(): bool
    {
        return !$this->isProtected();
    }

    public function isNotSuspended(): bool
    {
        return !$this->isSuspended();
    }

    public function isProtected(): bool
    {
        return $this->protected;
    }

    public function setProtected(bool $protected): self
    {
        $this->protected = $protected;

        return $this;
    }

    public function isSuspended(): bool
    {
        return $this->suspended;
    }

    public function setSuspended(bool $suspended): self
    {
        $this->suspended = $suspended;

        return $this;
    }

    public function removeToken(Token $tokens)
    {
        $this->tokens->removeElement($tokens);
    }

    public function setConfirmationToken($confirmationToken)
    {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }

    public function setExpired(bool $expired): self
    {
        $this->expired = $expired;

        return $this;
    }

    public function setName(string $name): self
    {
        $this->fullName = $name;

        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function setTotalTweets($totalTweets): self
    {
        $this->totalStatuses = $totalTweets;

        return $this;
    }

    public function totalTweets(): int
    {
        return $this->totalStatuses;
    }

    public function setRawDocument(string $rawDocument): self
    {
        $this->rawDocument = $rawDocument;

        return $this;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }
}
