<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface,
    Doctrine\Common\Persistence\ObjectManager,
    Doctrine\Common\Inflector\Inflector;
use WeavingTheWeb\Bundle\DashboardBundle\Entity\Perspective,
    WeavingTheWeb\Bundle\DashboardBundle\DBAL\Connection;

class PerspectiveData implements FixtureInterface
{
    const DEFAULT_QUERY = "# Show administration panel\n
SELECT per_id as id, per_name as name, per_value AS pre_sql, per_name as hid_name, per_value as btn_sql
FROM weaving_perspective
ORDER BY per_id DESC";

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $updateTemporaryDataQueries = [
            'valid_data_updating_perspective' => '# Update tmp_data' . "\n" .
                'Update tmp_data SET jsn_hash = md5(jsn_value) LIMIT 1;'
        ];

        $exportableDataQueries = [
            'exportable_perspective' => self::DEFAULT_QUERY
        ];

        $perspectiveDefaultType = Perspective::TYPE_DEFAULT;

        $propertiesCollection = [
            [
                'value' => self::DEFAULT_QUERY,
                'type' => $perspectiveDefaultType,
                'hash' => sha1(
                    'status:' . Perspective::STATUS_DEFAULT . ':' .
                    self::DEFAULT_QUERY
                )
            ],
            [
                'value' => self::DEFAULT_QUERY,
                'status' => Perspective::STATUS_PUBLIC,
                'type' => $perspectiveDefaultType,
                'hash' => sha1(self::DEFAULT_QUERY),
            ],
            [
                'name' => 'valid_data_updating_perspective',
                'value' => $updateTemporaryDataQueries['valid_data_updating_perspective'],
                'type' => $perspectiveDefaultType,
                'hash' => sha1($updateTemporaryDataQueries['valid_data_updating_perspective']),
            ],
            [
                'name' => 'exportable_perspective',
                'value' => $exportableDataQueries['exportable_perspective'],
                'status' => Perspective::STATUS_EXPORTABLE,
                'uuid' => '513E1A98-E71B-40A5-B639-24E8C3A4FBA2',
                'type' => $perspectiveDefaultType,
                'hash' => sha1(
                    'status:' . Perspective::STATUS_EXPORTABLE . ':' .
                    $exportableDataQueries['exportable_perspective']
                ),
            ],
        ];

        foreach ($propertiesCollection as $properties) {
            $perspective = new Perspective();

            foreach ($properties as $name => $value) {
                $classifiedName = Inflector::classify($name);
                $setter = 'set' . $classifiedName;
                $perspective->$setter($value);
            }

            $manager->persist($perspective);
        }

        $manager->flush();
    }
}
