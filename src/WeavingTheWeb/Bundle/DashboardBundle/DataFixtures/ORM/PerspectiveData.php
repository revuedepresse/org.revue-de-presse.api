<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface,
    Doctrine\Common\Persistence\ObjectManager;
use WeavingTheWeb\Bundle\DashboardBundle\Entity\Perspective,
    WeavingTheWeb\Bundle\DashboardBundle\DBAL\Connection;

class PerspectiveData implements FixtureInterface
{
    const DEFAULT_QUERY = '# Show administration panel
SELECT per_id as id, per_name as name, per_value AS pre_sql, per_name as hid_name, per_value as btn_sql
FROM weaving_perspective
ORDER BY per_id DESC';

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $perspective = new Perspective();
        $perspective->setValue(self::DEFAULT_QUERY);
        $perspective->setType(Connection::QUERY_TYPE_DEFAULT);
        $manager->persist($perspective);

        $manager->flush();

        $perspective= new Perspective();
        $perspective->setValue(self::DEFAULT_QUERY);
        $perspective->setStatus($perspective::STATUS_PUBLIC);
        $perspective->setType(Connection::QUERY_TYPE_DEFAULT);
        $perspective->setHash(sha1(self::DEFAULT_QUERY));
        $manager->persist($perspective);

        $manager->flush();
    }
}
