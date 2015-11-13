<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Entity\Awareness;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
trait UserAware
{
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
    public function setUser(UserInterface $user)
    {
        $this->user = $user;

        return $this;
    }
}
