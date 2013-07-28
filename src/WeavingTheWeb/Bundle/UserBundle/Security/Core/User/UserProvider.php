<?php

namespace WeavingTheWeb\Bundle\UserBundle\Security\Core\User;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException,
    Symfony\Component\Security\Core\Exception\UsernameNotFoundException,
    Symfony\Component\Security\Core\User\UserInterface as SecurityUserInterface;
use FOS\UserBundle\Security\UserProvider as BaseUserProvider;
use WTW\UserBundle\Model\User;

/**
 * Class UserProvider
 *
 * @package WTW\UserBundle\Security
 */
class UserProvider extends BaseUserProvider
{
    /**
     * {@inheritDoc}
     */
    public function refreshUser(SecurityUserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Expected an instance of WTW\UserBundle\Model\User, but got "%s".', get_class($user)));
        }

        if (null === $reloadedUser = $this->userManager->findUserBy(array('id' => $user->getId()))) {
            throw new UsernameNotFoundException(sprintf('User with ID "%d" could not be reloaded.', $user->getId()));
        }

        return $reloadedUser;
    }
}
