<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Json
 *
 * @ORM\Entity(repositoryClass="WeavingTheWeb\Bundle\ApiBundle\Repository\UserStreamRepository")
 * @ORM\Table(
 *      name="weaving_twitter_user_stream",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="unique_hash", columns={"ust_hash", "ust_access_token", "ust_full_name"}),
 *      },
 *      indexes={
 *          @ORM\index(name="hash", columns={"ust_hash"}),
 *          @ORM\index(name="screen_name", columns={"ust_full_name"}),
 *          @ORM\index(name="status_id", columns={"ust_status_id"})
 *      }
 * )
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class UserStream
{
    /**
     * @var integer
     *
     * @ORM\Column(name="ust_id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="ust_hash", type="string", length=40, nullable=true)
     */
    protected $hash;

    /**
     * @ORM\Column(name="ust_full_name", type="string", length=32)
     */
    protected $screenName;

    /**
     * @ORM\Column(name="ust_name", type="string", length=32)
     */
    protected $name;

    /**
     * @ORM\Column(name="ust_text", type="string", length=140)
     */
    protected $text;

    /**
     * @ORM\Column(name="ust_avatar", type="string", length=255)
     */
    protected $userAvatar;

    /**
     * @ORM\Column(name="ust_access_token", type="string", length=255)
     */
    protected $identifier;

    /**
     * @ORM\Column(name="ust_status_id", type="string", length=255, nullable=true)
     */
    protected $statusId;

    /**
     * @ORM\Column(name="ust_api_document", type="text", nullable=true)
     */
    protected $apiDocument;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="ust_created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="ust_updated_at", type="datetime", nullable=true)
     */
    protected $updatedAt;

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
     * @param string $hash
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Set screeName
     *
     * @param  string     $screenName
     * @return UserStream
     */
    public function setScreenName($screenName)
    {
        $this->screenName = $screenName;

        return $this;
    }

    /**
     * Get screeName
     *
     * @return string
     */
    public function getScreenName()
    {
        return $this->screenName;
    }

    /**
     * Set name
     *
     * @param  string     $name
     * @return UserStream
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
     * Set text
     *
     * @param  string     $text
     * @return UserStream
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get text
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set userAvatar
     *
     * @param  string     $userAvatar
     * @return UserStream
     */
    public function setUserAvatar($userAvatar)
    {
        $this->userAvatar = $userAvatar;

        return $this;
    }

    /**
     * Get userAvatar
     *
     * @return string
     */
    public function getUserAvatar()
    {
        return $this->userAvatar;
    }

    /**
     * Set identifier
     *
     * @param  string     $identifier
     * @return UserStream
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * Get identifier
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    public function setStatusId($statusId)
    {
        $this->statusId = $statusId;
    }

    public function getStatusId()
    {
        return $this->statusId;
    }

    public function setApiDocument($apiDocument)
    {
        $this->apiDocument = $apiDocument;
    }

    public function getApiDocument()
    {
        return $this->apiDocument;
    }

    /**
     * Set createdAt
     *
     * @param  \DateTime  $createdAt
     * @return UserStream
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
     * @param  \DateTime  $updatedAt
     * @return UserStream
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
}
