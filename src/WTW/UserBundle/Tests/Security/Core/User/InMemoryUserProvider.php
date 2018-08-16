<?php

namespace WTW\UserBundle\Tests\Security\Core\User;

use Symfony\Component\Security\Core\Exception\UsernameNotFoundException,
    Symfony\Component\Security\Core\Exception\UnsupportedUserException,
    WTW\UserBundle\Model\User as UserModel,
    Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class InMemoryUserProvider
 * @package WTW\UserBundle\Tests\Security\Core
 */
class InMemoryUserProvider implements UserProviderInterface
{
    protected $users;

    /**
     * Constructor.
     *
     * The user array is a hash where the keys are usernames and the values are
     * an array of attributes: 'password', 'enabled', and 'roles'.
     *
     * @param array $users An array of users
     */
    public function __construct(array $users = array())
    {
        foreach ($users as $username => $attributes) {
            $password = isset($attributes['password']) ? $attributes['password'] : null;
            $enabled = isset($attributes['enabled']) ? $attributes['enabled'] : true;
            $roles = isset($attributes['roles']) ? $attributes['roles'] : array();
            $user = new User($username, $password, $roles, $enabled, true, true, true);

            $this->createUser($user);
        }
    }

    /**
     * Adds a new User to the provider.
     *
     * @param UserModel $user A UserInterface instance
     *
     * @throws \LogicException
     */
    public function createUser(UserModel $user)
    {
        if (isset($this->users[strtolower($user->getUsername())])) {
            throw new \LogicException('Another user with the same username already exist.');
        }

        $this->users[strtolower($user->getUsername())] = $user;
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username)
    {
        if (!isset($this->users[strtolower($username)])) {
            $ex = new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
            $ex->setUsername($username);

            throw $ex;
        }

        $user = $this->users[strtolower($username)];

        return new User($user->getUsername(), $user->getPassword(), $user->getRoles(), $user->isEnabled(
            ), $user->isAccountNonExpired(),
            $user->isCredentialsNonExpired(), $user->isAccountNonLocked());
    }

    /**
     * {@inheritDoc}
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * {@inheritDoc}
     */
    public function supportsClass($class)
    {
        return $class === 'WTW\UserBundle\Tests\Entity\UserTest';
    }
}
