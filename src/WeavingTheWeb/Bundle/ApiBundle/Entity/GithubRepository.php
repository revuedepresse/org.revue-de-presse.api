<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * GithubRepo
 *
 * @ORM\Entity(repositoryClass="WeavingTheWeb\Bundle\ApiBundle\Repository\GithubRepository")
 * @ORM\Table(name="weaving_github_repositories")
 */
class GithubRepository
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="rep_github_id", type="integer")
     */
    private $githubId;

    /**
     * @var integer
     *
     * @ORM\Column(name="rep_forks", type="integer")
     */
    private $forks;

    /**
     * @var integer
     *
     * @ORM\Column(name="rep_watchers", type="integer")
     */
    private $watchers;

    /**
     * @var integer
     *
     * @ORM\Column(name="rep_status", type="integer")
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="rep_owner_id", type="integer")
     */
    private $ownerId;

    /**
     * @var string
     *
     * @ORM\Column(name="rep_owner", type="string", length=255)
     */
    private $owner;

    /**
     * @var string
     *
     * @ORM\Column(name="rep_language", type="string", length=255)
     */
    private $language;

    /**
     * @var string
     *
     * @ORM\Column(name="rep_name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="rep_avatar_url", type="string", length=255)
     */
    private $avatarUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="rep_clone_url", type="string", length=255)
     */
    private $cloneUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="rep_description", type="text")
     */
    private $description;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="rep_created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="rep_updated_at", type="datetime", nullable=true)
     */
    private $updatedAt;

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
     * Set githubId
     *
     * @param integer $githubId
     * @return GithubRepo
     */
    public function setGithubId($githubId)
    {
        $this->githubId = $githubId;

        return $this;
    }

    /**
     * Get githubId
     *
     * @return integer
     */
    public function getGithubId()
    {
        return $this->githubId;
    }

    /**
     * Set forks
     *
     * @param integer $forks
     * @return GithubRepo
     */
    public function setForks($forks)
    {
        $this->forks = $forks;

        return $this;
    }

    /**
     * Get forks
     *
     * @return integer
     */
    public function getForks()
    {
        return $this->forks;
    }

    /**
     * Set watchers
     *
     * @param integer $watchers
     * @return GithubRepo
     */
    public function setWatchers($watchers)
    {
        $this->watchers = $watchers;

        return $this;
    }

    /**
     * Get watchers
     *
     * @return integer
     */
    public function getWatchers()
    {
        return $this->watchers;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return GithubRepo
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set ownerId
     *
     * @param integer $ownerId
     * @return GithubRepo
     */
    public function setOwnerId($ownerId)
    {
        $this->ownerId = $ownerId;

        return $this;
    }

    /**
     * Get ownerId
     *
     * @return integer
     */
    public function getOwnerId()
    {
        return $this->ownerId;
    }

    /**
     * Set owner
     *
     * @param string $owner
     * @return GithubRepo
     */
    public function setOwner($owner)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * Get owner
     *
     * @return string
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set language
     *
     * @param string $language
     * @return GithubRepo
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Get language
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return GithubRepo
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
     * Set avatarUrl
     *
     * @param string $avatarUrl
     * @return GithubRepo
     */
    public function setAvatarUrl($avatarUrl)
    {
        $this->avatarUrl = $avatarUrl;

        return $this;
    }

    /**
     * Get avatarUrl
     *
     * @return string
     */
    public function getAvatarUrl()
    {
        return $this->avatarUrl;
    }

    /**
     * Set cloneUrl
     *
     * @param string $cloneUrl
     * @return GithubRepo
     */
    public function setCloneUrl($cloneUrl)
    {
        $this->cloneUrl = $cloneUrl;

        return $this;
    }

    /**
     * Get cloneUrl
     *
     * @return string
     */
    public function getCloneUrl()
    {
        return $this->cloneUrl;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return GithubRepo
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return GithubRepo
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
     * @return GithubRepo
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