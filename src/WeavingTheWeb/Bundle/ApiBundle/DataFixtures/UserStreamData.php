<?php

namespace WeavingTheWeb\Bundle\ApiBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface,
    Doctrine\Common\Persistence\ObjectManager;
use WeavingTheWeb\Bundle\ApiBundle\Entity\UserStream;

class UserStreamData implements FixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $properties = [
            'text' => 'This is a tweet text.',
            'identifier' => 'access token',
            'name' => 'Thierry Marianne',
            'screen_name' => 'thierrymarianne',
            'user_avatar' => 'http://avatar.url',
        ];

        $repository = new UserStream();

        $repository->setText($properties['text']);
        $repository->setUserAvatar($properties['user_avatar']);
        $repository->setName($properties['name']);
        $repository->setScreenName($properties['screen_name']);
        $repository->setIdentifier($properties['identifier']);
        $repository->setCreatedAt(new \DateTime());
        $repository->setUpdatedAt(new \DateTime());

        $manager->persist($repository);

        $manager->flush();
    }
}