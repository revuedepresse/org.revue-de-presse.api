<?php

namespace App\Status\Repository;

use Doctrine\ORM\EntityRepository;

class KeywordRepository extends EntityRepository
{
    public function getKeywords()
    {
        $connection = $this->getEntityManager()->getConnection();

        $query = <<<QUERY
SELECT keyword, SUM(occurrences) total_occurrences 
FROM keyword
WHERE keyword LIKE '#%'
AND publication_date_time > DATE_SUB(NOW(), INTERVAL 1 WEEK)
GROUP BY replace(keyword, ',', '')
ORDER BY total_occurrences DESC
LIMIT 100;
QUERY;

        $statement = $connection->executeQuery($query);
        $results = $statement->fetchAll();

        if (count($results) === 0) {
            return [];
        }

        return array_map(function ($record) {
            return (object) [
                'term' => $record['keyword'],
                'occurrences' => intval($record['total_occurrences'])
            ];
        }, $results);
    }
}
