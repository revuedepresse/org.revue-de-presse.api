<?php

namespace WeavingTheWeb\Bundle\UserBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture,
    Doctrine\Common\DataFixtures\OrderedFixtureInterface,
    Doctrine\Common\Persistence\ObjectManager;

use WTW\UserBundle\Tests\Security\Core\User\User;

class UserData extends AbstractFixture implements OrderedFixtureInterface
{
    public function getOrder()
    {
        return 300;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $rolesProperties = [
            [
                'username' => 'User',
                'password' => 'WN6!e1SfH92#8zbB#nnGKlrxHr*ounQJB^sML!Rb44Cs3I!Q^n',
                'email' => 'user@weaving-the-web.org',
                'enabled' => true,
                'username_canonical' => 'user',
                'user_non_expired' => true,
                'credentials_non_expired' => true,
                'twitter_id' => 1,
                'twitter_username' => 'user',
                'roles' => [],
            ]
        ];

        foreach ($rolesProperties as $userProperties) {
            $user = new User(
                $userProperties['username'],
                $userProperties['password'],
                $userProperties['roles'],
                $userProperties['enabled'],
                $userProperties['user_non_expired'],
                $userProperties['credentials_non_expired']
            );

            $user->setTwitterID($userProperties['twitter_id']);

            $user->setEmail($userProperties['email']);
            $user->setUsernameCanonical($userProperties['username_canonical']);
            $user->setTwitterUsername($userProperties['twitter_username']);

            /**
             * @var \WeavingTheWeb\Bundle\UserBundle\Entity\Role; $role
             */
            $role = $manager->merge($this->getReference('role_user'));
            $user->addRole($role);

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
}
