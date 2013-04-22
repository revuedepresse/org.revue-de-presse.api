<?php

namespace WTW\DashboardBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface,
    Doctrine\Common\Persistence\ObjectManager;
use WTW\DashboardBundle\Entity\Perspective,
    WTW\DashboardBundle\DBAL\Connection;

class PerspectiveData implements FixtureInterface
{
    const DEFAULT_QUERY = 'SELECT * FROM perspective';

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $json = new Perspective();
        $json->setValue(self::DEFAULT_QUERY);
        $json->setType(Connection::QUERY_TYPE_DEFAULT);
        $manager->persist($json);

        $manager->flush();

    }
}