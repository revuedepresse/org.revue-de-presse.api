<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Entity\OAuth;

use Doctrine\ORM\Mapping as ORM;

use WeavingTheWeb\Bundle\DashboardBundle\Entity\SelectionAwareInterface;

use WTW\UserBundle\Entity\User;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 * @ORM\Entity(repositoryClass="WeavingTheWeb\Bundle\DashboardBundle\Repository\OAuth\ClientRepository")
 * @ORM\Table(name="weaving_oauth_client",
 *      indexes={
 *          @ORM\Index(
 *              name="user",
 *              columns={"user"}
 *          )
 *      }
 * )
 */
class Client implements SelectionAwareInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="client_id", type="text")
     */
    private $clientId;

    /**
     * @ORM\Column(name="client_secret", type="text")
     */
    private $clientSecret;

    /**
     * @ORM\Column(name="redirect_uri", type="text")
     */
    private $redirectUri;

    /**
     * @var bool
     *
     * @ORM\Column(name="selected", type="boolean", options={"default": false})
     */
    private $selected = false;

    /**
     * @ORM\ManyToOne(targetEntity="\WTW\UserBundle\Entity\User", cascade={"all"})
     * @ORM\JoinColumn(name="user", referencedColumnName="usr_id")
     */
    protected $user;

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     * @return $this
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="selectedAt", type="datetime", nullable=true)
     */
    private $selectedAt;

    /**
     * @var \DateTime
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * @param $clientId
     * @param $clientSecret
     * @param $redirectUri
     */
    public function __construct($clientId, $clientSecret, $redirectUri)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;
        $this->createdAt = new \DateTime();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param mixed $clientId
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * @return mixed
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    /**
     * @param $clientSecret
     * @return $this
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRedirectUri()
    {
        return $this->redirectUri;
    }

    /**
     * @param $redirectUri
     * @return $this
     */
    public function setRedirectUri($redirectUri)
    {
        $this->redirectUri = $redirectUri;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return mixed
     */
    public function isSelected()
    {
        return $this->selected;
    }

    /**
     * @param $selected
     * @return $this
     */
    public function setSelected($selected) {
        $this->selected = $selected;
        if ($this->selected) {
            $this->selected = new \DateTime();
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function select()
    {
        $this->setSelected(true);

        return $this;
    }

    /**
     * @return $this
     */
    public function unselect()
    {
        $this->setSelected(false);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSelectedAt()
    {
        return $this->selectedAt;
    }

    /**
     * @param mixed $selectedAt
     * @return $this
     */
    public function setSelectedAt(\DateTime $selectedAt)
    {
        $this->selectedAt = $selectedAt;

        return $this;
    }
}
