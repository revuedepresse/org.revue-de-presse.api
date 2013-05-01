<?php

namespace WTW\DashboardBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Entity\User as BaseUSer;

/**
 * User
 *
 * @ORM\Table(name="weaving_user")
 * @ORM\Entity
 */
class User extends BaseUSer
{
    /**
     * @var integer
     *
     * @ORM\Column(name="usr_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
    * @var string
    *
    * @ORM\Column(name="usr_twitter_id", type="string", nullable=false)
    */
    protected $twitterID;

    /**
     * @var string
     *
     * @ORM\Column(name="usr_twitter_username", type="string", nullable=false)
     */
    protected $twitter_username;

    /**
     * @var integer
     *
     * @ORM\Column(name="grp_id", type="integer", nullable=true)
     */
    protected $groupId;

    /**
     * @var boolean
     *
     * @ORM\Column(name="usr_status", type="boolean", nullable=false)
     */
    protected $enabled;

    /**
     * @var string
     *
     * @ORM\Column(name="usr_user_name", type="string", length=255, nullable=false)
     */
    protected $userName;

    /**
     * @var integer
     *
     * @ORM\Column(name="usr_avatar", type="integer", nullable=true)
     */
    protected $avatar;

    /**
     * @var string
     *
     * @ORM\Column(name="usr_first_name", type="string", length=255, nullable=false)
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
     * @ORM\Column(name="usr_email", type="string", length=255, nullable=true)
     */
    protected $email;

    /**
     * @var string
     *
     * @ORM\Column(name="usr_phone", type="string", length=30, nullable=true)
     */
    protected $phone;

    /**
     * @var string
     *
     * @ORM\Column(name="usr_password", type="string", length=255, nullable=true)
     */
    protected $password;

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
     * @param integer $groupId
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
     * @param boolean $status
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
     * Set userName
     *
     * @param string $userName
     * @return User
     */
    public function setUserName($userName)
    {
        $this->userName = $userName;
    
        return $this;
    }

    /**
     * Get userName
     *
     * @return string 
     */
    public function getUserName()
    {
        return $this->userName;
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
     * Set firstName
     *
     * @param string $firstName
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
     * @param string $lastName
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
     * @param string $middleName
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
     * Set phone
     *
     * @param string $phone
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
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set fullName
     *
     * @param string $fullName
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
}