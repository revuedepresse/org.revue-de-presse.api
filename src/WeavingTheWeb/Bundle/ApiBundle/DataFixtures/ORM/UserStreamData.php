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
        $status = 'This is a tweet text.';
        $properties = [
            'text' => $status,
            'api_document' => json_encode(['text' => $status]),
            'identifier' => 'access token',
            'indexed' => false,
            'name' => 'Thierry Marianne',
            'screen_name' => 'thierrymarianne',
            'user_avatar' => 'http://avatar.url',
            'status_id' => 194987972,
        ];

        /**
         * TODO Rename user stream to user status
         */
        $userStream = new UserStream();

        $userStream->setText($properties['text']);
        $userStream->setApiDocument($properties['api_document']);
        $userStream->setUserAvatar($properties['user_avatar']);
        $userStream->setName($properties['name']);
        $userStream->setScreenName($properties['screen_name']);
        $userStream->setIdentifier($properties['identifier']);
        $userStream->setIndexed($properties['indexed']);
        $userStream->setStatusId($properties['status_id']);
        $userStream->setCreatedAt(new \DateTime());
        $userStream->setUpdatedAt(new \DateTime());

        $manager->persist($userStream);

        $manager->flush();
    }
}
