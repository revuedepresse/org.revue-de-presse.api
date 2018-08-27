<?php

namespace WeavingTheWeb\Bundle\UserBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture,
    Doctrine\Common\DataFixtures\OrderedFixtureInterface,
    Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use WeavingTheWeb\Bundle\UserBundle\Entity\RoleInterface;

use WTW\UserBundle\Tests\Security\Core\User\User;

class UserData extends AbstractFixture implements OrderedFixtureInterface,  ContainerAwareInterface
{
    /**
     * @var \Doctrine\Common\Persistence\ObjectManager $manager
     */
    private $manager;

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    private $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function getOrder()
    {
        return 300;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;

        $users = [
            [
                'username' => $this->container->getParameter('wtw.qa.user.username'),
                'password' => $this->container->getParameter('wtw.qa.user.password'),
                'email' => 'user@weaving-the-web.org',
                'enabled' => true,
                'username_canonical' => 'user',
                'user_non_expired' => true,
                'credentials_non_expired' => true,
                'twitter_id' => 1,
                'twitter_username' => 'user',
                'api_key' => sha1('user'.'secret'),
                'roles' => [],
            ], [
                'username' => $this->container->getParameter('wtw.qa.super.username'),
                'password' => $this->container->getParameter('wtw.qa.super.password'),
                'email' => 'super@weaving-the-web.org',
                'enabled' => true,
                'username_canonical' => 'super',
                'user_non_expired' => true,
                'credentials_non_expired' => true,
                'twitter_id' => 2,
                'twitter_username' => 'super',
                'roles' => ['super_admin'],
            ]
        ];

        foreach ($users as $userProperties) {
            $user = new User(
                $userProperties['username'],
                $userProperties['password'],
                array(), // Roles declared as an empty array first of all
                $userProperties['enabled'],
                $userProperties['user_non_expired'],
                $userProperties['credentials_non_expired']
            );

            if (array_key_exists('api_key', $userProperties)) {
                $user->apiKey = $userProperties['api_key'];
            }

            $user->setTwitterID($userProperties['twitter_id']);

            $user->setEmail($userProperties['email']);
            $user->setUsernameCanonical($userProperties['username_canonical']);
            $user->setTwitterUsername($userProperties['twitter_username']);

            $this->addUserRoles($user, $userProperties);

            /** @var \WeavingTheWeb\Bundle\ApiBundle\Entity\Token $firstToken */
            $firstToken = $manager->merge($this->getReference('user_token_1'));
            $user->addToken($firstToken);

            /** @var \WeavingTheWeb\Bundle\ApiBundle\Entity\Token $secondToken */
            $secondToken = $manager->merge($this->getReference('user_token_2'));
            $user->addToken($secondToken);

            $this->addReference($userProperties['username_canonical'], $user);

            $manager->persist($user);
        }

        $manager->flush();
    }

    /**
     * @param $user
     * @param $userProperties
     */
    protected function addUserRoles(User $user, $userProperties)
    {
        $roleReferencePrefix = 'role_';
        $userRoleReferenceName = $roleReferencePrefix . RoleInterface::ROLE_USER;

        /** @var \WeavingTheWeb\Bundle\UserBundle\Entity\Role $role */
        $role = $this->manager->merge($this->getReference($userRoleReferenceName));
        $user->addRole($role);

        if (array_key_exists('roles', $userProperties) && count($userProperties['roles']) > 0) {
            foreach ($userProperties['roles'] as $roleName) {
                $roleConstant = constant(RoleInterface::class . '::' . strtoupper('role_' . $roleName));
                $roleReferenceName = $roleReferencePrefix . $roleConstant;

                /** @var \WeavingTheWeb\Bundle\UserBundle\Entity\Role; $role */
                $role = $this->manager->merge($this->getReference($roleReferenceName));
                $user->addRole($role);
            }
        }
    }
}
