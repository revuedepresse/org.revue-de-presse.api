<?php

namespace App\DataFixtures\ORM;

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
                'username' => $this->container->getParameter('weaving_the_web.quality_assurance.user.username'),
                'password' => $this->container->getParameter('weaving_the_web.quality_assurance.user.password'),
                'email' => 'user@weaving-the-web.org',
                'enabled' => true,
                'username_canonical' => 'user',
                'twitter_id' => 1,
                'twitter_username' => 'user',
                'api_key' => sha1('user'.'secret'),
                'roles' => [],
                'total_subscribees' => 0,
                'total_subscriptions' => 0,
            ], [
                'username' => $this->container->getParameter('weaving_the_web.quality_assurance.super.username'),
                'password' => $this->container->getParameter('weaving_the_web.quality_assurance.super.password'),
                'email' => 'super@weaving-the-web.org',
                'enabled' => true,
                'username_canonical' => 'super',
                'twitter_id' => 2,
                'twitter_username' => 'super',
                'roles' => ['super_admin'],
                'total_subscribees' => 0,
                'total_subscriptions' => 0,
            ]
        ];

        foreach ($users as $userProperties) {
            $user = new User(
                $userProperties['username'],
                $userProperties['password'],
                array(), // Roles declared as an empty array first of all
                $userProperties['enabled'],
                null,     // Deprecated since removal
                null // of the implementation of UserInterface
            );

            if (array_key_exists('api_key', $userProperties)) {
                $user->apiKey = $userProperties['api_key'];
            }

            $user->setTwitterID($userProperties['twitter_id']);

            $user->setEmail($userProperties['email']);
            $user->setUsernameCanonical($userProperties['username_canonical']);
            $user->setTwitterUsername($userProperties['twitter_username']);

            /** @var \WeavingTheWeb\Bundle\ApiBundle\Entity\Token $firstToken */
            $firstToken = $manager->merge($this->getReference('user_token_1'));
            $user->addToken($firstToken);

            /** @var \WeavingTheWeb\Bundle\ApiBundle\Entity\Token $secondToken */
            $secondToken = $manager->merge($this->getReference('user_token_2'));
            $user->addToken($secondToken);

            $this->addReference($userProperties['username_canonical'], $user);

            $user->totalSubscriptions = $userProperties['total_subscriptions'];
            $user->totalSubscribees = $userProperties['total_subscribees'];

            $manager->persist($user);
        }

        $manager->flush();
    }
}
