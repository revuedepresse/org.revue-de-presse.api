<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Security\Core\User\UserInterface;

use WeavingTheWeb\Bundle\DashboardBundle\Entity\Awareness\CreationAware,
    WeavingTheWeb\Bundle\DashboardBundle\Entity\Awareness\CreationAwareInterface,
    WeavingTheWeb\Bundle\DashboardBundle\Entity\Awareness\SelectionAware,
    WeavingTheWeb\Bundle\DashboardBundle\Entity\Awareness\SelectionAwareInterface,
    WeavingTheWeb\Bundle\DashboardBundle\Entity\Awareness\UserAware,
    WeavingTheWeb\Bundle\DashboardBundle\Entity\Awareness\UserAwareInterface;

/**
 * @ORM\Entity(repositoryClass="\WeavingTheWeb\Bundle\DashboardBundle\Repository\RemoteRepository")
 * @ORM\Table(name="weaving_remote")
 */
class Remote implements SelectionAwareInterface, UserAwareInterface, CreationAwareInterface
{
    use CreationAware,
        SelectionAware,
        UserAware;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="host", type="string", length=255)
     */
    protected $host;

    /**
     * @var string
     *
     * @ORM\Column(name="access_token", type="text")
     */
    protected $accessToken;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    protected $updatedAt;

    public function __construct($host, $accessToken)
    {
        $this->host = $host;
        $this->accessToken = $accessToken;
        $this->createdAt = new \DateTime();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $host
     * @return $this
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * @param string $accessToken
     * @return $this
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
