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
            'nearly_valid_data_updating_perspective' => '# Update tmp_data' . "\n" . 'Update data SET jsn_hash = md5(jsn_value) LIMIT 1;',
            'invalid_data_updating_perspective' => '# Update data' . "\n" . 'Update weaving_json SET jsn_hash = md5(jsn_value) LIMIT 1;',
            'valid_data_updating_perspective' => '# Update tmp_data' . "\n" . 'Update tmp_data SET jsn_hash = md5(jsn_value) LIMIT 1;'
        ];

        $propertiesCollection = [
            [
                'value' => self::DEFAULT_QUERY,
                'type' => Connection::QUERY_TYPE_DEFAULT,
            ], [
                'value' => self::DEFAULT_QUERY,
                'status' => Perspective::STATUS_PUBLIC,
                'type' => Connection::QUERY_TYPE_DEFAULT,
                'hash' => sha1(self::DEFAULT_QUERY),
            ], [
                'name' => 'invalid_data_updating_perspective',
                'value' => $updateTemporaryDataQueries['invalid_data_updating_perspective'],
                'type' => Connection::QUERY_TYPE_DEFAULT,
                'hash' => sha1($updateTemporaryDataQueries['invalid_data_updating_perspective']),
            ], [
                'name' => 'valid_data_updating_perspective',
                'value' => $updateTemporaryDataQueries['valid_data_updating_perspective'],
                'type' => Connection::QUERY_TYPE_DEFAULT,
                'hash' => sha1($updateTemporaryDataQueries['valid_data_updating_perspective']),
            ], [
                'name' => 'nearly_valid_data_updating_perspective',
                'value' => $updateTemporaryDataQueries['nearly_valid_data_updating_perspective'],
                'type' => Connection::QUERY_TYPE_DEFAULT,
                'hash' => sha1($updateTemporaryDataQueries['nearly_valid_data_updating_perspective']),
            ]
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
