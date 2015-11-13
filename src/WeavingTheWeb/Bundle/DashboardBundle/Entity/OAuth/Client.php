<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Entity\OAuth;

use Doctrine\ORM\Mapping as ORM;

use WeavingTheWeb\Bundle\DashboardBundle\Entity\Awareness\CreationAware,
    WeavingTheWeb\Bundle\DashboardBundle\Entity\Awareness\CreationAwareInterface,
    WeavingTheWeb\Bundle\DashboardBundle\Entity\Awareness\SelectionAware,
    WeavingTheWeb\Bundle\DashboardBundle\Entity\Awareness\SelectionAwareInterface,
    WeavingTheWeb\Bundle\DashboardBundle\Entity\Awareness\UserAware,
    WeavingTheWeb\Bundle\DashboardBundle\Entity\Awareness\UserAwareInterface;

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
class Client implements SelectionAwareInterface, UserAwareInterface, CreationAwareInterface
{
    use CreationAware,
        SelectionAware,
        UserAware;

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
}
