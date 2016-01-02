<?php

namespace WeavingTheWeb\Bundle\UserBundle\Tests\Security\Core\User;

use Doctrine\Common\Collections\ArrayCollection,
    Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Security\Core\Exception\UsernameNotFoundException,
    Symfony\Component\Security\Core\Exception\UnsupportedUserException,
    Symfony\Component\Security\Core\User\UserInterface,
    Symfony\Component\Security\Core\User\UserProviderInterface;

use WeavingTheWeb\Bundle\UserBundle\Entity\Role;

/**
 * @package WTW\UserBundle\Tests\Security\Core
 */
class InMemoryUserProvider implements UserProviderInterface
{
    protected $users;

    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    private $objectManager;

    /**
     * @param ObjectManager $entityManager
     */
    public function setObjectManager(ObjectManager $entityManager)
    {
        $this->objectManager = $entityManager;
    }

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
     * @param UserInterface $user
     * @throws \Exception
     */
    public function createUser(UserInterface $user)
    {
        if (isset($this->users[strtolower($user->getUsername())])) {
            throw new \LogicException('Another user with the same username already exist.');
        }

        $this->users[strtolower($user->getUsername())] = $this->refreshUserRoles($user);
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username)
    {
        if (!isset($this->users[strtolower($username)])) {
            $this->raiseUserNotFoundException($username);
        }

        $userRepository = $this->objectManager->getRepository('WTWUserBundle:User');

        /** @var User $user */
        $userCandidate = $userRepository->findOneBy(['username' => $username]);
        if (!is_null($userCandidate)) {
            return $userCandidate;
        }

        $user = $this->users[strtolower($username)];
        $user = new User(
            $user->getUsername(),
            $user->getPassword(),
            $user->getRoles(),
            $user->isEnabled(),
            $user->isAccountNonExpired(),
            $user->isCredentialsNonExpired(),
            $user->isAccountNonLocked()
        );
        $user->setEmail($user->getUsername() . '@' . 'example.com');

        return $user;
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

    /**
     * @param $role
     * @throws \Exception
     */
    protected function raiseInvalidRoleException($role)
    {
        throw new \Exception(
            sprintf(
                'Invalid role with name %s for in memory user provider',
                (string) $role
            )
        );
    }

    /**
     * @param UserInterface $user
     * @return UserInterface
     * @throws \Exception
     */
    protected function refreshUserRoles(UserInterface $user)
    {
        $roles = $user->getRoles();
        if (count($roles) > 0) {
            $userRoles = new ArrayCollection();
            foreach ($roles as $role) {
                $roleRepository = $this->objectManager->getRepository('WeavingTheWebUserBundle:Role');

                try {
                    if (is_string($role)) {
                        $existingRole = $roleRepository->findOneBy(['role' => $role]);
                    } elseif (is_object($role) and $role instanceof Role) {
                        $existingRole = $roleRepository->findOneBy(['role' => $role->getRole()]);
                    }

                    if (!isset($existingRole) || is_null($existingRole)) {
                        $this->raiseInvalidRoleException($role);
                    } else {
                        $userRoles->add($existingRole);
                    }

                    $roles = $userRoles->toArray();
                } catch (\Exception $exception) {
                    // Can fail at cache warm-up if the Role table has not been created yet.
                    // Keep the roles originally provided in this case

                    break;
                }
            }

            $userClass = get_class($user);
            $user = new $userClass($user->getUsername(), $user->getPassword(), $roles);
        }

        return $user;
    }

    /**
     * @param $username
     */
    protected function raiseUserNotFoundException($username)
    {
        $ex = new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
        $ex->setUsername($username);

        throw $ex;
    }
}
