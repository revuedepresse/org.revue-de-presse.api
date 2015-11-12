<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Form\Type;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
interface UserAwareTypeInterface
{
    public function setUser(UserInterface $user);
}
