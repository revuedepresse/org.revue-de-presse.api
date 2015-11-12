<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
abstract class AbstractUserAwareType extends AbstractType implements UserAwareTypeInterface
{
    /**
     * @var UserInterface
     */
    protected $user;

    /**
     * @param UserInterface $user
     */
    public function setUser(UserInterface $user)
    {
        $this->user = $user;
    }
}
