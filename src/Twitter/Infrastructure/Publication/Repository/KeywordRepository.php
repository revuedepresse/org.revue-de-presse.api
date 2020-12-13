<?php

namespace App\Twitter\Infrastructure\Publication\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityRepository;

class KeywordRepository extends ServiceEntityRepository
{
    /**
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getKeywords(\DateTime $startDate, \DateTime $endDate)
    {

        $queryTemplate = <<<QUERY
            SELECT
            keyword,
            SUM(occurrences) total_occurrences,
            IF(h.total_retweets = 0, 1, h.total_retweets) weight 
            FROM keyword k
            LEFT JOIN highlight h ON h.status_id = k.status_id
            WHERE 1
            AND keyword LIKE '#%' 
            {{ dates }}
            GROUP BY REPLACE(REPLACE(keyword, ',', ''), ':', '')
            ORDER BY h.total_retweets, weight DESC
            LIMIT 100;
QUERY;

        $results = $this->executeQueryAfterBindingParameters(
            $startDate,
            $endDate,
            $queryTemplate
        );

        if (count($results) === 0) {
            return [
                [
                    'term' => '',
                    'occurrences' => 1
                ]
            ];
        }

        return array_map(function ($record) {
            return (object) [
                'term' => strtr(
                    $record['keyword'],
                    [
                        ',' => '',
                        ':' =>  '',
                    ]
                ),
                'occurrences' => (int) $record['weight']
            ];
        }, $results);
    }

    /**
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param           $queryTemplate
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function executeQueryAfterBindingParameters(
        \DateTime $startDate,
        \DateTime $endDate,
        $queryTemplate): array
    {
        $parameters = [
            $startDate,
            $endDate,
        ];
        $types = [
            Type::DATETIME,
            Type::DATETIME,
        ];
        $query = str_replace(
            '{{ dates }}',
            'AND k.publication_date_time >= ?
                AND k.publication_date_time <= ?',
            $queryTemplate
        );
        if ($startDate->format('Y-m-d') === $endDate->format('Y-m-d')) {
            $parameters = [
                $startDate,
            ];
            $types = [
                Type::DATETIME,
            ];
            $query = str_replace(
                '{{ dates }}',
                'AND DATE(k.publication_date_time) = DATE(?)',
                $queryTemplate
            );
        }

        $connection = $this->getEntityManager()->getConnection();

        /** @var Connection connection */
        $statement = $connection->executeQuery(
            $query,
            $parameters,
            $types
        );

        return $statement->fetchAll();
    }
}
