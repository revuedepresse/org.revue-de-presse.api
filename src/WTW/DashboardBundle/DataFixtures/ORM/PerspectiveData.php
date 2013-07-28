<?php

namespace WTW\DashboardBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface,
    Doctrine\Common\Persistence\ObjectManager;
use WTW\DashboardBundle\Entity\Perspective,
    WTW\DashboardBundle\DBAL\Connection;

class PerspectiveData implements FixtureInterface
{
    const DEFAULT_QUERY = '# Update administration panel
SELECT per_id as id, per_name as name, per_value AS pre_sql, per_name as hid_name, per_value as btn_sql
FROM weaving_perspective
ORDER BY per_id DESC';

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
