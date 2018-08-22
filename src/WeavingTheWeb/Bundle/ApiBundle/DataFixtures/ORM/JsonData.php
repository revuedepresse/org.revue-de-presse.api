<?php

namespace WeavingTheWeb\Bundle\ApiBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface,
    Doctrine\Common\Persistence\ObjectManager;
use WeavingTheWeb\Bundle\ApiBundle\Entity\Json;

class JsonData implements FixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $userStream = file_get_contents(__DIR__ . '/../../Resources/json/fixtures/twitter_user_stream.json');
        $json = new Json();
        $json->setValue($userStream);
        $json->setHash($hash = md5($userStream));
        $json->setStatus(1);
        $json->setType(2);
        $manager->persist($json);

        $feed = file_get_contents(__DIR__ . '/../../Resources/json/fixtures/facebook_feed.json');
        $json = new Json();

        $decodedDocuments = json_decode($feed, true)['data'][0]['value'];
        $documents = json_encode($decodedDocuments, true);

        $json->setValue($documents);
        $json->setHash($hash = md5($feed));
        $json->setStatus(1);
        $json->setType(0);
        $manager->persist($json);

        $manager->flush();
    }
}
