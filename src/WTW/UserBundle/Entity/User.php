<?php

namespace WTW\UserBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection,
    Doctrine\ORM\Mapping as ORM;
use WTW\UserBundle\Model\User as BaseUser;

/**
 * Class User
 *
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 * @package WTW\UserBundle\Entity
 *
 * @ORM\Table(name="weaving_user")
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="usr_position_in_hierarchy", type="integer")
 * @ORM\DiscriminatorMap({"1" = "User", "0" = "\WTW\UserBundle\Tests\Security\Core\User\User"})
 */
class User extends BaseUser
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
    * @ORM\Column(name="usr_twitter_id", type="integer", nullable=true)
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
     * @ORM\Column(name="grp_id", type="integer", nullable=true)
     */
    protected $groupId;

    /**
     * @var integer
     *
     * @ORM\Column(name="usr_avatar", type="integer", nullable=true)
     */
    protected $avatar;

    /**
     * @var string
     *
     * @ORM\Column(name="usr_first_name", type="string", length=255, nullable=true)
     */
    protected $firstName;

    /**
     * @var string
     *
     * @ORM\Column(name="usr_full_name", type="string", length=255, nullable=true)
     */
    protected $fullName;

    /**
     * @var string
     *
     * @ORM\Column(name="usr_last_name", type="string", length=255, nullable=true)
     */
    protected $lastName;

    /**
     * @var string
     *
     * @ORM\Column(name="usr_middle_name", type="string", length=255, nullable=true)
     */
    protected $middleName;

    /**
     * @var string
     *
     * @ORM\Column(name="usr_phone", type="string", length=30, nullable=true)
     */
    protected $phone;

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
     * @var string
     *
     * @ORM\Column(name="usr_password", type="string", length=255, nullable=true)
     */
    protected $password;

    /**
     * @var \DateTime
     * @ORM\Column(name="usr_password_requested_at", type="datetime", nullable=true)
     */
    protected $passwordRequestedAt;

    /**
     * The salt to use for hashing
     *
     * @var string
     * @ORM\Column(name="usr_salt", type="string", length=255, nullable=true)
     */
    protected $salt;

    /**
     * @var boolean
     * @ORM\Column(name="usr_locked", type="boolean")
     */
    protected $locked;

    /**
     * @var boolean
     * @ORM\Column(name="usr_credentials_expired", type="boolean", nullable=true)
     */
    protected $credentialsExpired;

    /**
     * @var \DateTime
     * @ORM\Column(name="usr_credentials_expires_at", type="datetime", nullable=true)
     */
    protected $credentialsExpireAt;

    /**
     * Random string sent to the user email address in order to verify it
     *
     * @var string
     * @ORM\Column(name="usr_confirmation_token", type="string", length=255, nullable=true)
     */
    protected $confirmationToken;

    /**
     * @var boolean
     * @ORM\Column(name="usr_expired", type="boolean", nullable=true)
     */
    protected $expired;

    /**
     * @var \DateTime
     * @ORM\Column(name="usr_expires_at", type="datetime", nullable=true)
     */
    protected $expiresAt;

    /**
     * @var \DateTime
     * @ORM\Column(name="usr_last_login", type="datetime", nullable=true)
     */
    protected $lastLogin;

    /**
     * @ORM\ManyToMany(targetEntity="WeavingTheWeb\Bundle\UserBundle\Entity\Group")
     * @ORM\JoinTable(name="weaving_user_group",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="usr_id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="rol_id")}
     * )
     */
    protected $groups;

    /**
     * @var \DateTime
     */
    protected $positionInHierarchy;

    /**
     * @ORM\ManyToMany(targetEntity="WeavingTheWeb\Bundle\UserBundle\Entity\Role", inversedBy="users")
     * @ORM\JoinTable(name="weaving_user_role",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="usr_id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="role_id", referencedColumnName="id")}
     * )
     */
    protected $roles;

    /**
     * @ORM\ManyToMany(targetEntity="WeavingTheWeb\Bundle\ApiBundle\Entity\Token", inversedBy="users")
     * @ORM\JoinTable(name="weaving_user_token",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="usr_id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="token_id", referencedColumnName="id")}
     * )
     */
    protected $tokens;

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
     * Set groupId
     *
     * @param  integer $groupId
     * @return User
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * Get groupId
     *
     * @return integer
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * Set status
     *
     * @param  boolean $status
     * @return User
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
     * @param  integer $avatar
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
     * Set firstName
     *
     * @param  string $firstName
     * @return User
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get firstName
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set lastName
     *
     * @param  string $lastName
     * @return User
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get lastName
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set middleName
     *
     * @param  string $middleName
     * @return User
     */
    public function setMiddleName($middleName)
    {
        $this->middleName = $middleName;

        return $this;
    }

    /**
     * Get middleName
     *
     * @return string
     */
    public function getMiddleName()
    {
        return $this->middleName;
    }

    /**
     * Set email
     *
     * @param  string $email
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
     * Set phone
     *
     * @param  string $phone
     * @return User
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set password
     *
     * @param  string $password
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
     * Set twitterID
     *
     * @param $twitterId
     */
    public function setTwitterID($twitterId)
    {
      $this->twitterID = $twitterId;
      $this->salt = '';
    }

    /**
    * Set twitter_username
    *
    * @param string $twitterUsername
    */
    public function setTwitterUsername($twitterUsername)
    {
      $this->twitter_username = $twitterUsername;
    }

    /**
    * Get twitter_username
    *
    * @return string
    */
    public function getTwitterUsername()
    {
      return $this->twitter_username;
    }

    /**
     * Get twitterID
     *
     * @return string
     */
    public function getTwitterID()
    {
        return $this->twitterID;
    }

    /**
     * Set enabled
     *
     * @param  boolean $enabled
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
     * Set fullName
     *
     * @param  string $fullName
     * @return User
     */
    public function setFullName($fullName)
    {
        $this->fullName = $fullName;

        return $this;
    }

    /**
     * Get fullName
     *
     * @return string
     */
    public function getFullName()
    {
        return $this->fullName;
    }

    /**
     * Set usernameCanonical
     *
     * @param  string $usernameCanonical
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
     * @param  string $emailCanonical
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
     * Set salt
     *
     * @param  string $salt
     * @return User
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * Get salt
     *
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * Set locked
     *
     * @param  boolean $locked
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
     * @param  boolean $expired
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

    /**
     * Set username
     *
     * @param  string $username
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Set credentialsExpired
     *
     * @param  boolean $credentialsExpired
     * @return User
     */
    public function setCredentialsExpired($credentialsExpired)
    {
        $this->credentialsExpired = $credentialsExpired;

        return $this;
    }

    /**
     * Get credentialsExpired
     *
     * @return boolean
     */
    public function getCredentialsExpired()
    {
        return $this->credentialsExpired;
    }

    /**
     * Set expiresAt
     *
     * @param  \DateTime $expiresAt
     * @return User
     */
    public function setExpiresAt(\DateTime $expiresAt = null)
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    /**
     * Get expiresAt
     *
     * @return \DateTime
     */
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    /**
     * Set lastLogin
     *
     * @param  \DateTime $lastLogin
     * @return User
     */
    public function setLastLogin(\DateTime $lastLogin = null)
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    /**
     * Get lastLogin
     *
     * @return \DateTime
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * Set passwordRequestedAt
     *
     * @param  \DateTime $passwordRequestedAt
     * @return User
     */
    public function setPasswordRequestedAt(\DateTime $passwordRequestedAt = null)
    {
        $this->passwordRequestedAt = $passwordRequestedAt;

        return $this;
    }

    /**
     * Get passwordRequestedAt
     *
     * @return \DateTime
     */
    public function getPasswordRequestedAt()
    {
        return $this->passwordRequestedAt;
    }

    /**
     * Set credentialsExpireAt
     *
     * @param  \DateTime $credentialsExpireAt
     * @return User
     */
    public function setCredentialsExpireAt(\DateTime $credentialsExpireAt = null)
    {
        $this->credentialsExpireAt = $credentialsExpireAt;

        return $this;
    }

    /**
     * Get credentialsExpireAt
     *
     * @return \DateTime
     */
    public function getCredentialsExpireAt()
    {
        return $this->credentialsExpireAt;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->roles = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add roles
     *
     * @param  \WeavingTheWeb\Bundle\UserBundle\Entity\Role $roles
     * @return User
     */
    public function addRole($roles)
    {
        $this->roles[] = $roles;

        return $this;
    }

    /**
     * Remove roles
     *
     * @param \WeavingTheWeb\Bundle\UserBundle\Entity\Role $roles
     */
    public function removeRole($roles)
    {
        $this->roles->removeElement($roles);
    }

    /**
     * Get roles
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRoles()
    {
        if (is_null($this->roles) || (!$this->roles instanceof ArrayCollection)) {
            $collection = new ArrayCollection();

            foreach ($this->roles as $role) {
                $role = (string) $role;
                if (!$collection->contains($role)) {
                    $collection->add($role);
                }
            }

            if (!$collection->contains(self::ROLE_DEFAULT)) {
                $collection->add(self::ROLE_DEFAULT);
            }

            $this->roles = $collection;
        }

        return $this->roles->toArray();
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
}