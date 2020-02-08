<?php

namespace App\DataFixtures\ORM;

use App\Aggregate\Entity\TimelyStatus;
use Doctrine\Common\DataFixtures\FixtureInterface,
    Doctrine\Common\Persistence\ObjectManager;
use WeavingTheWeb\Bundle\ApiBundle\Entity\Aggregate;
use App\Api\Entity\Status;

class StatusData implements FixtureInterface
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

        $userStatus = $this->makeUserStatus($properties);
        $manager->persist($userStatus);

        $encodedUserStream = file_get_contents(__DIR__ . '/../../Tests/Resources/fixtures/user-stream.base64');
        $serializedEntitiesWhichHaveBeenMoved = strtr(
            base64_decode($encodedUserStream),
            [
                '\UserStream' => '\Status',
                '48:' => '44:',
            ]
        );
        $userStatusCollection = unserialize($serializedEntitiesWhichHaveBeenMoved);

        $aggregate = new Aggregate(
            'thierrymarianne',
            'press'
        );
        $manager->persist($aggregate);

        $timelyStatus = new TimelyStatus(
            $userStatus,
            $aggregate,
            new \DateTime('now', new \DateTimeZone('UTC'))
        );

        $manager->persist($timelyStatus);

        foreach ($userStatusCollection as $userStatus) {
            $manager->persist($userStatus);
        }


        $manager->flush();
    }

    /**
     * @param array $properties
     *
     * @return Status
     */
    protected function makeUserStatus(array $properties)
    {
        $status = new Status();

        $status->setText($properties['text']);
        $status->setApiDocument($properties['api_document']);
        $status->setUserAvatar($properties['user_avatar']);
        $status->setName($properties['name']);
        $status->setScreenName($properties['screen_name']);
        $status->setIdentifier($properties['identifier']);
        $status->setIndexed($properties['indexed']);
        $status->setStatusId($properties['status_id']);
        $status->setCreatedAt(new \DateTime());
        $status->setUpdatedAt(new \DateTime());

        return $status;
    }
}
